<?php

declare(strict_types=1);

use App\Models\Memo;
use App\Models\User;

uses()->group('policies');

test('認証済みユーザーは言伝一覧を閲覧できる', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Memo::class))->toBeTrue();
});

test('作成者は自分の言伝を閲覧できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $memo))->toBeTrue();
});

test('作成者以外は他人の言伝を閲覧できない', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $owner->id]);

    expect($otherUser->can('view', $memo))->toBeFalse();
});

test('認証済みユーザーは言伝を作成できる', function () {
    $user = User::factory()->create();

    expect($user->can('create', Memo::class))->toBeTrue();
});

test('作成者は自分の言伝を更新できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $memo))->toBeTrue();
});

test('作成者以外は他人の言伝を更新できない', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $owner->id]);

    expect($otherUser->can('update', $memo))->toBeFalse();
});

test('作成者は自分の言伝を削除できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    expect($user->can('delete', $memo))->toBeTrue();
});

test('作成者以外は他人の言伝を削除できない', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $owner->id]);

    expect($otherUser->can('delete', $memo))->toBeFalse();
});

test('作成者は削除した言伝を復元できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);
    $memo->delete();

    expect($user->can('restore', $memo))->toBeTrue();
});

test('作成者以外は削除した言伝を復元できない', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $owner->id]);
    $memo->delete();

    expect($otherUser->can('restore', $memo))->toBeFalse();
});

test('作成者は言伝を完全削除できる', function () {
    $user = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $user->id]);

    expect($user->can('forceDelete', $memo))->toBeTrue();
});

test('作成者以外は言伝を完全削除できない', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $memo = Memo::factory()->create(['user_id' => $owner->id]);

    expect($otherUser->can('forceDelete', $memo))->toBeFalse();
});
