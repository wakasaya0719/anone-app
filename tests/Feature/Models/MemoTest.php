<?php

declare(strict_types=1);

use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Models\User;

uses()->group('models');

test('言伝を作成できる', function () {
    $user = User::factory()->create();

    $memo = Memo::factory()->create([
        'user_id' => $user->id,
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'status' => MemoStatus::DRAFT,
    ]);

    expect($memo->title)->toBe('テストタイトル')
        ->and($memo->content)->toBe('テスト本文')
        ->and($memo->status)->toBe(MemoStatus::DRAFT)
        ->and($memo->user_id)->toBe($user->id);
});

test('言伝は投稿者とリレーションを持つ', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    expect($memo->user)->toBeInstanceOf(User::class)
        ->and($memo->user->id)->toBe($user->id);
});

test('感情タグをEnumとしてキャストできる', function () {
    $memo = Memo::factory()->create([
        'emotion_tag' => EmotionTag::JOY,
    ]);

    expect($memo->emotion_tag)->toBeInstanceOf(EmotionTag::class)
        ->and($memo->emotion_tag)->toBe(EmotionTag::JOY)
        ->and($memo->emotion_tag->label())->toBe('嬉しい')
        ->and($memo->emotion_tag->emoji())->toBe('😊');
});

test('ステータスをEnumとしてキャストできる', function () {
    $memo = Memo::factory()->draft()->create();

    expect($memo->status)->toBeInstanceOf(MemoStatus::class)
        ->and($memo->status)->toBe(MemoStatus::DRAFT)
        ->and($memo->status->label())->toBe('下書き');
});

test('公開済みスコープが機能する', function () {
    Memo::factory()->published()->count(3)->create();
    Memo::factory()->draft()->count(2)->create();

    $publishedMemos = Memo::published()->get();

    expect($publishedMemos)->toHaveCount(3)
        ->and($publishedMemos->every(fn($memo) => $memo->status === MemoStatus::PUBLISHED))->toBeTrue();
});

test('下書きスコープが機能する', function () {
    Memo::factory()->published()->count(3)->create();
    Memo::factory()->draft()->count(2)->create();

    $draftMemos = Memo::draft()->get();

    expect($draftMemos)->toHaveCount(2)
        ->and($draftMemos->every(fn($memo) => $memo->status === MemoStatus::DRAFT))->toBeTrue();
});

test('isPublishedメソッドが正しく判定する', function () {
    $publishedMemo = Memo::factory()->published()->create();
    $draftMemo = Memo::factory()->draft()->create();

    expect($publishedMemo->isPublished())->toBeTrue()
        ->and($draftMemo->isPublished())->toBeFalse();
});

test('isDraftメソッドが正しく判定する', function () {
    $publishedMemo = Memo::factory()->published()->create();
    $draftMemo = Memo::factory()->draft()->create();

    expect($publishedMemo->isDraft())->toBeFalse()
        ->and($draftMemo->isDraft())->toBeTrue();
});

test('ソフトデリートが機能する', function () {
    $memo = Memo::factory()->create();
    $memoId = $memo->id;

    $memo->delete();

    expect(Memo::find($memoId))->toBeNull()
        ->and(Memo::withTrashed()->find($memoId))->not->toBeNull();
});

test('ユーザーは複数の言伝を持つ', function () {
    $user = User::factory()->create();
    Memo::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->memos)->toHaveCount(3)
        ->and($user->memos->every(fn($memo) => $memo->user_id === $user->id))->toBeTrue();
});
