<?php

declare(strict_types=1);

use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Models\User;
use Livewire\Volt\Volt;

uses()->group('livewire', 'ui');

// 一覧画面
test('認証済みユーザーは言伝一覧を閲覧できる', function () {
    $user = User::factory()->create();
    Memo::factory(5)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('memos.index'))
        ->assertOk()
        ->assertSeeLivewire('memos.index');
});

test('言伝一覧は自分の言伝のみ表示される', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $myMemo = Memo::factory()->create(['user_id' => $user->id, 'title' => '自分の言伝']);
    $otherMemo = Memo::factory()->create(['user_id' => $otherUser->id, 'title' => '他人の言伝']);

    $this->actingAs($user)
        ->get(route('memos.index'))
        ->assertSee('自分の言伝')
        ->assertDontSee('他人の言伝');
});

test('言伝一覧は検索できる', function () {
    $user = User::factory()->create();

    Memo::factory()->create(['user_id' => $user->id, 'title' => 'Laravel テスト']);
    Memo::factory()->create(['user_id' => $user->id, 'title' => 'PHP プログラミング']);

    Volt::test('memos.index')
        ->actingAs($user)
        ->set('search', 'Laravel')
        ->assertSee('Laravel テスト')
        ->assertDontSee('PHP プログラミング');
});

test('言伝一覧はステータスでフィルタできる', function () {
    $user = User::factory()->create();

    Memo::factory()->published()->create(['user_id' => $user->id, 'title' => '公開済み']);
    Memo::factory()->draft()->create(['user_id' => $user->id, 'title' => '下書き']);

    Volt::test('memos.index')
        ->actingAs($user)
        ->set('status', MemoStatus::PUBLISHED->value)
        ->assertSee('公開済み')
        ->assertDontSee('下書き');
});

test('言伝を一覧から削除できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    Volt::test('memos.index')
        ->actingAs($user)
        ->call('delete', $memo->id)
        ->assertHasNoErrors();

    expect(Memo::find($memo->id))->toBeNull();
});

// 詳細画面
test('認証済みユーザーは自分の言伝を閲覧できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('memos.show', $memo))
        ->assertOk()
        ->assertSee($memo->title)
        ->assertSee($memo->content);
});

test('他人の言伝は閲覧できない', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('memos.show', $memo))
        ->assertForbidden();
});

test('言伝詳細から削除できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    Volt::test('memos.show', ['memo' => $memo])
        ->actingAs($user)
        ->call('delete')
        ->assertRedirect(route('memos.index'));

    expect(Memo::find($memo->id))->toBeNull();
});

// 作成画面
test('認証済みユーザーは言伝作成画面を表示できる', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('memos.create'))
        ->assertOk()
        ->assertSeeLivewire('memos.create');
});

test('言伝を作成できる', function () {
    $user = User::factory()->create();

    Volt::test('memos.create')
        ->actingAs($user)
        ->set('title', 'テストタイトル')
        ->set('content', 'テスト本文')
        ->set('emotion_tag', EmotionTag::JOY->value)
        ->set('status', MemoStatus::PUBLISHED->value)
        ->call('create')
        ->assertHasNoErrors();

    expect(Memo::where('title', 'テストタイトル')->exists())->toBeTrue();
});

test('必須項目がないと言伝を作成できない', function () {
    $user = User::factory()->create();

    Volt::test('memos.create')
        ->actingAs($user)
        ->set('title', '')
        ->set('content', '')
        ->call('create')
        ->assertHasErrors(['title', 'content']);
});

test('下書きとして保存できる', function () {
    $user = User::factory()->create();

    Volt::test('memos.create')
        ->actingAs($user)
        ->set('title', 'テストタイトル')
        ->set('content', 'テスト本文')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $memo = Memo::where('title', 'テストタイトル')->first();
    expect($memo->status)->toBe(MemoStatus::DRAFT);
});

test('公開として保存できる', function () {
    $user = User::factory()->create();

    Volt::test('memos.create')
        ->actingAs($user)
        ->set('title', 'テストタイトル')
        ->set('content', 'テスト本文')
        ->call('publish')
        ->assertHasNoErrors();

    $memo = Memo::where('title', 'テストタイトル')->first();
    expect($memo->status)->toBe(MemoStatus::PUBLISHED)
        ->and($memo->published_at)->not->toBeNull();
});

// 編集画面
test('認証済みユーザーは自分の言伝を編集できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('memos.edit', $memo))
        ->assertOk()
        ->assertSeeLivewire('memos.edit');
});

test('他人の言伝は編集できない', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('memos.edit', $memo))
        ->assertForbidden();
});

test('言伝を更新できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    Volt::test('memos.edit', ['memo' => $memo])
        ->actingAs($user)
        ->set('title', '更新後のタイトル')
        ->set('content', '更新後の本文')
        ->call('update')
        ->assertHasNoErrors();

    expect($memo->fresh()->title)->toBe('更新後のタイトル')
        ->and($memo->fresh()->content)->toBe('更新後の本文');
});

test('下書きを公開できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->draft()->create(['user_id' => $user->id]);

    Volt::test('memos.edit', ['memo' => $memo])
        ->actingAs($user)
        ->set('title', $memo->title)
        ->set('content', $memo->content)
        ->call('publish')
        ->assertHasNoErrors();

    expect($memo->fresh()->status)->toBe(MemoStatus::PUBLISHED)
        ->and($memo->fresh()->published_at)->not->toBeNull();
});

test('公開済みを下書きに戻せない', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->published()->create(['user_id' => $user->id]);

    Volt::test('memos.edit', ['memo' => $memo])
        ->actingAs($user)
        ->set('title', $memo->title)
        ->set('content', $memo->content)
        ->set('status', MemoStatus::DRAFT->value)
        ->call('update')
        ->assertHasErrors('status');

    expect($memo->fresh()->status)->toBe(MemoStatus::PUBLISHED);
});
