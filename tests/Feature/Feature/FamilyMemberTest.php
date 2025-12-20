<?php

declare(strict_types=1);

use App\Models\FamilyMember;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

test('ユーザーは家族メンバーを作成できる', function () {
    $user = User::factory()->create();

    actingAs($user);

    Volt::test('settings.family')
        ->set('name', '太郎')
        ->set('role', 'son')
        ->set('birthDate', '2015-05-10')
        ->call('save');

    assertDatabaseHas('family_members', [
        'user_id' => $user->id,
        'name' => '太郎',
        'role' => 'son',
    ]);
});

test('ユーザーは家族メンバーを削除できる', function () {
    $user = User::factory()->create();
    $member = FamilyMember::factory()->create([
        'user_id' => $user->id,
    ]);

    actingAs($user);

    Volt::test('settings.family')
        ->call('delete', $member->id);

    expect(FamilyMember::find($member->id))->toBeNull();
});

test('ユーザーは他人の家族メンバーを削除できない', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $member = FamilyMember::factory()->create([
        'user_id' => $user2->id,
    ]);

    actingAs($user1);

    Volt::test('settings.family')
        ->call('delete', $member->id);

    expect(FamilyMember::find($member->id))->not->toBeNull();
});

test('ユーザーはデフォルト送信者を設定できる', function () {
    $user = User::factory()->create();
    $member = FamilyMember::factory()->create([
        'user_id' => $user->id,
        'name' => 'お父さん',
    ]);

    actingAs($user);

    Volt::test('settings.family')
        ->call('setDefaultSender', $member->id);

    $member->refresh();
    $user->refresh();

    expect($member->is_default_sender)->toBeTrue();
    expect($user->display_name)->toBe('お父さん');
});

test('家族メンバーの年齢が正しく計算される', function () {
    $member = FamilyMember::factory()->create([
        'birth_date' => now()->subYears(10),
    ]);

    expect($member->age())->toBe(10);
});

test('生年月日がnullの場合、年齢はnullを返す', function () {
    $member = FamilyMember::factory()->create([
        'birth_date' => null,
    ]);

    expect($member->age())->toBeNull();
});

test('ユーザーはデフォルト送信者名を取得できる', function () {
    $user = User::factory()->create([
        'display_name' => 'お母さん',
    ]);

    expect($user->defaultSenderName())->toBe('お母さん');
});

test('デフォルト送信者が設定されている場合、その名前を返す', function () {
    $user = User::factory()->create([
        'display_name' => 'お母さん',
    ]);

    FamilyMember::factory()->create([
        'user_id' => $user->id,
        'name' => 'パパ',
        'is_default_sender' => true,
    ]);

    expect($user->defaultSenderName())->toBe('パパ');
});

test('家族設定画面が表示される', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('settings.family'))
        ->assertOk()
        ->assertSee('家族設定');
});
