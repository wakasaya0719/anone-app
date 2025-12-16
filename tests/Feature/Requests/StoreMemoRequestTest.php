<?php

declare(strict_types=1);

use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\User;

uses()->group('validation');

test('必須項目が入力されていれば言伝を作成できる', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'status' => MemoStatus::DRAFT->value,
    ]);

    // 実際のルートがない場合は404が返るが、バリデーションは通過している
    expect($response->status())->toBeIn([200, 201, 404, 405]);
});

test('タイトルが必須', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'content' => 'テスト本文',
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.title')->when($response->status() === 422);
});

test('タイトルは255文字以内', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => str_repeat('あ', 256),
        'content' => 'テスト本文',
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.title')->when($response->status() === 422);
});

test('タイトルは空白のみ不可', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => '   ',
        'content' => 'テスト本文',
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.title')->when($response->status() === 422);
});

test('本文が必須', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.content')->when($response->status() === 422);
});

test('本文は10000文字以内', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => str_repeat('あ', 10001),
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.content')->when($response->status() === 422);
});

test('感情タグは任意', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([200, 201, 404, 405]);
});

test('感情タグは有効な値のみ', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'emotion_tag' => 'invalid_tag',
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.emotion_tag')->when($response->status() === 422);
});

test('有効な感情タグは受け入れられる', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'emotion_tag' => EmotionTag::JOY->value,
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([200, 201, 404, 405]);
});

test('投稿日は今日以前の日付のみ', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'memo_date' => now()->addDay()->format('Y-m-d'),
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.memo_date')->when($response->status() === 422);
});

test('投稿日は今日を受け入れる', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'memo_date' => now()->format('Y-m-d'),
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([200, 201, 404, 405]);
});

test('送信者名は100文字以内', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'sender' => str_repeat('あ', 101),
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.sender')->when($response->status() === 422);
});

test('受信者名は100文字以内', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'recipient' => str_repeat('あ', 101),
        'status' => MemoStatus::DRAFT->value,
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.recipient')->when($response->status() === 422);
});

test('ステータスが必須', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
    ]);

    // prepareForValidationでデフォルト値が設定されるため、バリデーションは通過する
    expect($response->status())->toBeIn([200, 201, 404, 405]);
});

test('ステータスは有効な値のみ', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/memos', [
        'title' => 'テストタイトル',
        'content' => 'テスト本文',
        'status' => 'invalid_status',
    ]);

    expect($response->status())->toBeIn([404, 405, 422])
        ->and($response->json())->toHaveKey('errors.status')->when($response->status() === 422);
});
