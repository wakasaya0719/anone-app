<?php

declare(strict_types=1);

use App\Models\FamilyMember;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\title;
use function Livewire\Volt\uses;

layout('components.layouts.app');
title('家族設定');

// WithFileUploadsトレイトを使用
uses([WithFileUploads::class]);

state([
    'name' => '',
    'role' => '',
    'birthDate' => '',
    'photo' => null,
    'editingId' => null,
    'existingPhotoPath' => null,
    'removeExistingPhoto' => false,
]);

rules([
    'name' => 'required|string|max:50',
    'role' => 'required|string|max:20',
    'birthDate' => 'nullable|date|before:today',
    'photo' => 'nullable|image|max:2048',
]);

$members = computed(function () {
    return FamilyMember::query()
        ->where('user_id', auth()->id())
        ->orderBy('display_order')
        ->orderBy('created_at')
        ->get();
});

$removePhoto = function () {
    $this->photo = null;
};

$removeExistingPhoto = function () {
    $this->removeExistingPhoto = true;
    $this->existingPhotoPath = null;
};

$edit = function ($id) {
    $member = FamilyMember::query()
        ->where('user_id', auth()->id())
        ->findOrFail($id);

    $this->editingId = $member->id;
    $this->name = $member->name;
    $this->role = $member->role;
    $this->birthDate = $member->birth_date ? $member->birth_date->format('Y-m-d') : '';
    $this->existingPhotoPath = $member->photo_path;
    $this->removeExistingPhoto = false;

    // フォームまでスクロール
    $this->dispatch('scroll-to-form');
};

$cancelEdit = function () {
    $this->reset(['name', 'role', 'birthDate', 'photo', 'editingId', 'existingPhotoPath', 'removeExistingPhoto']);
};

$save = function () {
    $validated = $this->validate();

    if ($this->editingId) {
        // 更新処理
        $member = FamilyMember::query()
            ->where('user_id', auth()->id())
            ->findOrFail($this->editingId);

        $photoPath = $member->photo_path;

        // 既存写真を削除する場合
        if ($this->removeExistingPhoto && $photoPath) {
            Storage::disk('public')->delete($photoPath);
            $photoPath = null;
        }

        // 新しい写真をアップロード
        if ($this->photo) {
            // 古い写真を削除
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $this->photo->store('family_members', 'public');
        }

        $member->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'birth_date' => $validated['birthDate'] ?? null,
            'photo_path' => $photoPath,
        ]);

        session()->flash('message', '家族メンバーを更新しました。');
    } else {
        // 新規作成処理
        $photoPath = null;
        if ($this->photo) {
            $photoPath = $this->photo->store('family_members', 'public');
        }

        FamilyMember::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'role' => $validated['role'],
            'birth_date' => $validated['birthDate'] ?? null,
            'photo_path' => $photoPath,
            'display_order' => FamilyMember::where('user_id', auth()->id())->count(),
        ]);

        session()->flash('message', '家族メンバーを追加しました。');
    }

    $this->reset(['name', 'role', 'birthDate', 'photo', 'editingId', 'existingPhotoPath', 'removeExistingPhoto']);
};

$delete = function ($id) {
    $member = FamilyMember::where('user_id', auth()->id())
        ->where('id', $id)
        ->first();

    if ($member) {
        // 写真を削除
        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
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

<div class="max-w-4xl mx-auto p-6" id="family-form">
    <flux:heading size="xl">家族設定</flux:heading>
    <flux:subheading>メモの送信者・受信者として表示される家族メンバーを登録します</flux:subheading>

    @if (session('message'))
        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    <!-- 登録・編集フォーム -->
    <div class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <flux:heading size="lg">
            {{ $editingId ? '家族メンバーを編集' : '新しい家族メンバーを追加' }}
        </flux:heading>

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

            <flux:field>
                <flux:label>顔写真（任意）</flux:label>
                
                {{-- 既存の写真（編集時） --}}
                @if ($editingId && $existingPhotoPath && !$removeExistingPhoto)
                    <div class="flex items-center gap-4 mb-2">
                        <img src="{{ Storage::url($existingPhotoPath) }}" 
                            alt="現在の写真"
                            class="h-20 w-20 rounded-full object-cover ring-2 ring-warmth-300 shadow-sm">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">現在の写真</p>
                            <flux:button type="button" wire:click="removeExistingPhoto" variant="danger" size="sm">
                                この写真を削除
                            </flux:button>
                        </div>
                    </div>
                @endif
                
                {{-- 新しい写真のプレビュー --}}
                @if ($photo)
                    <div class="flex items-center gap-4 mb-2">
                        <img src="{{ $photo->temporaryUrl() }}" 
                            alt="新しい写真プレビュー"
                            class="h-20 w-20 rounded-full object-cover ring-2 ring-blue-300 shadow-sm">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">新しい写真</p>
                            <flux:button type="button" wire:click="removePhoto" variant="danger" size="sm">
                                削除
                            </flux:button>
                        </div>
                    </div>
                @endif
                
                {{-- アップロード --}}
                <input type="file" 
                    wire:model="photo" 
                    accept="image/*"
                    class="mt-2 block w-full text-sm text-soft-700 dark:text-soft-300
                           file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 
                           file:text-sm file:font-semibold file:bg-warmth-100 file:text-warmth-700
                           hover:file:bg-warmth-200 dark:file:bg-soft-700 dark:file:text-warmth-300
                           dark:hover:file:bg-soft-600 transition-colors cursor-pointer">
                
                <div wire:loading wire:target="photo" class="text-sm text-warmth-600 dark:text-warmth-400">
                    📤 アップロード中...
                </div>
                
                <flux:error name="photo" />
                <flux:description>推奨: 正方形、最大2MB</flux:description>
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? '更新' : '追加' }}
                </flux:button>
                
                @if ($editingId)
                    <flux:button type="button" wire:click="cancelEdit" variant="ghost">
                        キャンセル
                    </flux:button>
                @endif
            </div>
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
                    <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg shadow
                        {{ $editingId === $member->id ? 'ring-2 ring-blue-500' : '' }}">
                        <div class="flex items-center gap-4 flex-1">
                            {{-- 顔写真 --}}
                            @if ($member->photo_path)
                                <img src="{{ Storage::url($member->photo_path) }}" 
                                    alt="{{ $member->name }}の写真"
                                    class="h-16 w-16 rounded-full object-cover ring-2 ring-warmth-300 shadow-sm">
                            @else
                                <div class="h-16 w-16 rounded-full bg-warmth-100 dark:bg-soft-700 flex items-center justify-center text-2xl">
                                    👤
                                </div>
                            @endif
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <span class="font-medium text-lg">{{ $member->name }}</span>
                                    @if ($member->is_default_sender)
                                        <flux:badge color="blue">デフォルト送信者</flux:badge>
                                    @endif
                                    @if ($editingId === $member->id)
                                        <flux:badge color="yellow">編集中</flux:badge>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    続柄: {{ $this->getRoleLabel($member->role) }}
                                    @if ($member->birth_date)
                                        ・ {{ $member->age() }}歳
                                    @endif
                                </p>
                            </div>
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
                                wire:click="edit({{ $member->id }})"
                                size="sm"
                                variant="primary">
                                編集
                            </flux:button>
                            
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

    <!-- 家族への案内状セクション -->
    <div class="mt-12 p-6 bg-gradient-to-br from-warmth-50 to-soft-50 dark:from-gray-800 dark:to-gray-850 rounded-lg shadow-lg border border-warmth-200 dark:border-gray-700">
        <div class="flex items-start gap-4">
            <div class="text-4xl">📄</div>
            <div class="flex-1">
                <flux:heading size="lg">家族への案内状</flux:heading>
                <flux:subheading class="mt-2">
                    万が一の時に備えて、家族にこのアプリの存在を知らせる案内状を作成できます。
                </flux:subheading>

                <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg">
                    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                        <p><strong>📋 案内状の内容：</strong></p>
                        <ul class="list-disc list-inside ml-4 space-y-1">
                            <li>このアプリの説明</li>
                            <li>アクセス方法（URLとログイン手順）</li>
                            <li>パスワードリセットの方法</li>
                            <li>サポート連絡先</li>
                            <li>QRコード貼付欄</li>
                        </ul>

                        <p class="mt-4"><strong>💡 使い方：</strong></p>
                        <ol class="list-decimal list-inside ml-4 space-y-1">
                            <li>下のボタンからPDFをダウンロード</li>
                            <li>印刷して、大切な場所に保管</li>
                            <li>家族が見つけやすい場所に置いておく</li>
                        </ol>

                        <div class="mt-4 p-3 bg-warmth-50 dark:bg-gray-700 rounded border border-warmth-200 dark:border-gray-600">
                            <p class="text-xs text-warmth-800 dark:text-warmth-200">
                                ⚠️ <strong>注意：</strong>この案内状には、あなたのメールアドレスが記載されます。
                                家族がパスワードリセットを行うには、登録メールアドレスへのアクセスが必要です。
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('family-guide.download') }}" 
                        class="inline-flex items-center px-6 py-3 bg-gray-900 hover:bg-gray-800 dark:bg-gray-800 dark:hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition-colors duration-200">
                        📥 案内状をダウンロード（PDF）
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('scroll-to-form', () => {
            document.getElementById('family-form').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        });
    });
</script>
