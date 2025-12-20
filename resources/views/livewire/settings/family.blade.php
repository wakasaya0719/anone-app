<?php

declare(strict_types=1);

use function Livewire\Volt\{state, mount, computed, layout, title, rules};
use App\Models\FamilyMember;

layout('components.layouts.app');
title('家族設定');

state([
    'name' => '',
    'role' => '',
    'birthDate' => '',
    'editingId' => null,
]);

rules([
    'name' => 'required|string|max:50',
    'role' => 'required|string|max:20',
    'birthDate' => 'nullable|date|before:today',
]);

$members = computed(function () {
    return FamilyMember::query()
        ->where('user_id', auth()->id())
        ->orderBy('display_order')
        ->orderBy('created_at')
        ->get();
});

$save = function () {
    $validated = $this->validate();

    FamilyMember::create([
        'user_id' => auth()->id(),
        'name' => $validated['name'],
        'role' => $validated['role'],
        'birth_date' => $validated['birthDate'] ?? null,
        'display_order' => FamilyMember::where('user_id', auth()->id())->count(),
    ]);

    $this->reset(['name', 'role', 'birthDate']);

    session()->flash('message', '家族メンバーを追加しました。');
};

$delete = function ($id) {
    $member = FamilyMember::where('user_id', auth()->id())
        ->where('id', $id)
        ->first();

    if ($member) {
        $member->delete();
        session()->flash('message', '家族メンバーを削除しました。');
    }
};

$setDefaultSender = function ($id) {
    // 全てのメンバーのデフォルトフラグをオフ
    FamilyMember::where('user_id', auth()->id())
        ->update(['is_default_sender' => false]);

    // 選択したメンバーのみオン
    $member = FamilyMember::where('user_id', auth()->id())
        ->where('id', $id)
        ->first();

    if ($member) {
        $member->update(['is_default_sender' => true]);

        // ユーザーの表示名も更新
        auth()->user()->update(['display_name' => $member->name]);

        session()->flash('message', "デフォルト送信者を「{$member->name}」に設定しました。");
    }
};

$getRoleLabel = function (string $role): string {
    return match ($role) {
        'father' => '父親',
        'mother' => '母親',
        'son' => '息子',
        'daughter' => '娘',
        'grandfather' => '祖父',
        'grandmother' => '祖母',
        'other' => 'その他',
        default => $role,
    };
};

?>

<div class="max-w-4xl mx-auto p-6">
    <flux:heading size="xl">家族設定</flux:heading>
    <flux:subheading>メモの送信者・受信者として表示される家族メンバーを登録します</flux:subheading>

    @if (session('message'))
        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    <!-- 登録フォーム -->
    <div class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <flux:heading size="lg">新しい家族メンバーを追加</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <flux:field>
                <flux:label>名前</flux:label>
                <flux:input wire:model="name" type="text" placeholder="例：太郎、花子、お母さん" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>続柄</flux:label>
                <select wire:model="role" required
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">選択してください</option>
                    <option value="father">父親</option>
                    <option value="mother">母親</option>
                    <option value="son">息子</option>
                    <option value="daughter">娘</option>
                    <option value="grandfather">祖父</option>
                    <option value="grandmother">祖母</option>
                    <option value="other">その他</option>
                </select>
                <flux:error name="role" />
            </flux:field>

            <flux:field>
                <flux:label>生年月日（任意）</flux:label>
                <flux:input wire:model="birthDate" type="date" />
                <flux:description>年齢を自動表示するために使用します</flux:description>
                <flux:error name="birthDate" />
            </flux:field>

            <flux:button type="submit" variant="primary">追加</flux:button>
        </form>
    </div>

    <!-- 登録済み家族メンバー一覧 -->
    <div class="mt-8">
        <flux:heading size="lg">登録済み家族メンバー</flux:heading>

        @if ($this->members->isEmpty())
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                まだ家族メンバーが登録されていません。
            </p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($this->members as $member)
                    <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <span class="font-medium text-lg">{{ $member->name }}</span>
                                @if ($member->is_default_sender)
                                    <flux:badge color="blue">デフォルト送信者</flux:badge>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                続柄: {{ $this->getRoleLabel($member->role) }}
                                @if ($member->birth_date)
                                    ・ {{ $member->age() }}歳
                                @endif
                            </p>
                        </div>

                        <div class="flex gap-2">
                            @if (!$member->is_default_sender)
                                <flux:button
                                    wire:click="setDefaultSender({{ $member->id }})"
                                    size="sm"
                                    variant="ghost">
                                    デフォルトに設定
                                </flux:button>
                            @endif
                            <flux:button
                                wire:click="delete({{ $member->id }})"
                                wire:confirm="本当に削除しますか？"
                                size="sm"
                                variant="danger">
                                削除
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
