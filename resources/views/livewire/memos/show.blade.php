<?php

use function Livewire\Volt\{layout, mount, state, title};
use App\Models\Memo;
use Illuminate\Support\Facades\Storage;

layout('components.layouts.app');

// 状態管理
state(['memo']);

// 初期化
mount(function (Memo $memo) {
    $this->authorize('view', $memo);
    $this->memo = $memo->load('user.familyMembers');
    $this->title = $memo->title;
});

// 言伝削除
$delete = function () {
    $this->authorize('delete', $this->memo);
    
    $this->memo->delete();
    
    return $this->redirect(route('memos.index'), navigate: true);
};

?>

<div class="space-y-6 animate-gentle-fade-in">
    {{-- ヘッダー --}}
    <div class="flex items-center justify-between rounded-2xl bg-white/80 p-4 shadow-md backdrop-blur-sm dark:bg-soft-800/80">
        <flux:button 
            href="{{ route('memos.index') }}" 
            wire:navigate 
            variant="ghost" 
            icon="arrow-left"
            class="hover:bg-warmth-100 dark:hover:bg-soft-700"
        >
            一覧に戻る
        </flux:button>

        <div class="flex gap-2">
            <flux:button 
                href="{{ route('memos.edit', $memo) }}" 
                wire:navigate 
                variant="primary" 
                icon="pencil"
                class="bg-gradient-to-r from-warmth-500 to-coral-500 shadow-md"
            >
                編集
            </flux:button>

            <flux:button
                wire:click="delete"
                wire:confirm="本当に削除しますか？この操作は取り消せません。"
                variant="danger"
                icon="trash"
                class="shadow-md"
            >
                削除
            </flux:button>
        </div>
    </div>

    {{-- メインコンテンツ - より温かみのあるデザイン --}}
    <div class="overflow-hidden rounded-2xl border border-warmth-200 bg-white shadow-lg dark:border-soft-700 dark:bg-soft-800">
        {{-- ヘッダー部分 --}}
        <div class="border-b border-warmth-200 bg-gradient-to-r from-warmth-50 to-coral-50 p-8 dark:border-soft-700 dark:from-soft-800 dark:to-soft-700">
            <h1 class="text-3xl font-bold text-warmth-800 dark:text-warmth-200">
                {{ $memo->title }}
            </h1>

            {{-- メタ情報 --}}
            <div class="mt-6 flex flex-wrap items-center gap-4">
                {{-- ステータス --}}
                <flux:badge :variant="$memo->status->color()" size="sm" class="shadow-sm">
                    {{ $memo->status->label() }}
                </flux:badge>

                {{-- 感情タグ --}}
                @if ($memo->emotion_tag)
                    <span class="emotion-tag">
                        <span class="text-xl">{{ $memo->emotion_tag->emoji() }}</span>
                        <span>{{ $memo->emotion_tag->label() }}</span>
                    </span>
                @endif

                {{-- 日付 --}}
                @if ($memo->memo_date)
                    <div class="flex items-center gap-2 text-sm text-soft-600 dark:text-soft-400">
                        <flux:icon.calendar variant="micro" />
                        <span>{{ $memo->memo_date->format('Y年n月j日') }}</span>
                    </div>
                @endif

                {{-- 公開日時 --}}
                @if ($memo->published_at)
                    <div class="flex items-center gap-2 text-sm text-soft-600 dark:text-soft-400">
                        <flux:icon.globe-alt variant="micro" />
                        <span>{{ $memo->published_at->format('Y年n月j日 H:i') }} 公開</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- 送受信者情報 --}}
        @if ($memo->sender || $memo->recipient)
            <div class="border-b border-warmth-200 bg-warmth-50/50 px-8 py-6 dark:border-soft-700 dark:bg-soft-900/50">
                <div class="grid gap-6 md:grid-cols-2">
                    @if ($memo->sender)
                        <div class="rounded-xl bg-white/60 p-4 dark:bg-soft-800/60">
                            <div class="flex items-center gap-3">
                                {{-- 送信者の顔写真 --}}
                                @php
                                    $senderMember = $memo->user->familyMembers
                                        ->where('is_default_sender', true)
                                        ->first();
                                @endphp
                                @if ($senderMember?->photo_path)
                                    <img src="{{ Storage::url($senderMember->photo_path) }}" 
                                        alt="{{ $memo->sender }}の写真"
                                        class="h-12 w-12 rounded-full object-cover ring-2 ring-warmth-300 shadow-sm flex-shrink-0">
                                @else
                                    <div class="h-12 w-12 rounded-full bg-warmth-200 dark:bg-warmth-700 flex items-center justify-center text-lg flex-shrink-0">
                                        💝
                                    </div>
                                @endif
                                
                                <div class="flex-1">
                                    <div class="mb-2 text-sm font-medium text-warmth-700 dark:text-warmth-300">
                                        送信者
                                    </div>
                                    <p class="text-lg font-semibold text-warmth-900 dark:text-warmth-100">
                                        {{ $memo->sender }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($memo->recipient)
                        <div class="rounded-xl bg-white/60 p-4 dark:bg-soft-800/60">
                            <div class="flex items-center gap-3">
                                {{-- 受信者の顔写真 --}}
                                @php
                                    $recipientMember = $memo->user->familyMembers
                                        ->where('name', $memo->recipient)
                                        ->first();
                                @endphp
                                @if ($recipientMember?->photo_path)
                                    <img src="{{ Storage::url($recipientMember->photo_path) }}" 
                                        alt="{{ $memo->recipient }}の写真"
                                        class="h-12 w-12 rounded-full object-cover ring-2 ring-coral-300 shadow-sm flex-shrink-0">
                                @else
                                    <div class="h-12 w-12 rounded-full bg-coral-200 dark:bg-coral-700 flex items-center justify-center text-lg flex-shrink-0">
                                        🎁
                                    </div>
                                @endif
                                
                                <div class="flex-1">
                                    <div class="mb-2 text-sm font-medium text-coral-700 dark:text-coral-300">
                                        受信者
                                    </div>
                                    <p class="text-lg font-semibold text-coral-900 dark:text-coral-100">
                                        {{ $memo->recipient }}
                                        @if ($memo->recipient_age)
                                            <span class="ml-2 inline-flex items-center rounded-full bg-coral-100 px-3 py-1 text-sm font-medium text-coral-800 dark:bg-coral-900/30 dark:text-coral-200">
                                                {{ $memo->recipient_age }}歳
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- 本文 --}}
        <div class="p-8">
            <div class="mb-4 flex items-center gap-2 text-sm font-medium text-warmth-700 dark:text-warmth-300">
                <span>✍️</span>
                <span>言伝の内容</span>
            </div>
            <div class="prose prose-lg max-w-none dark:prose-invert">
                <p class="whitespace-pre-wrap leading-relaxed text-soft-800 dark:text-soft-200">{{ $memo->content }}</p>
            </div>
        </div>

        {{-- 添付写真（あれば） --}}
        @if ($memo->photo_path)
            <div class="border-t border-warmth-200 bg-warmth-50/30 p-8 dark:border-soft-700 dark:bg-soft-900/30">
                <div class="mb-4 flex items-center gap-2 text-sm font-medium text-warmth-700 dark:text-warmth-300">
                    <span>📷</span>
                    <span>添付写真</span>
                </div>
                <div class="flex justify-center">
                    <img src="{{ Storage::url($memo->photo_path) }}" 
                        alt="添付写真"
                        class="max-w-2xl w-full rounded-xl shadow-lg object-cover">
                </div>
            </div>
        @endif
    </div>

    {{-- フッター情報 --}}
    <div class="rounded-2xl border border-warmth-200 bg-gradient-to-r from-warmth-50 to-coral-50 p-6 text-sm dark:border-soft-700 dark:from-soft-800 dark:to-soft-700">
        <div class="flex items-center justify-between text-soft-600 dark:text-soft-400">
            <div class="flex items-center gap-2">
                <span>👤</span>
                <span>投稿者:</span>
                <span class="font-semibold text-warmth-800 dark:text-warmth-200">
                    {{ $memo->user->name }}
                </span>
            </div>
            <div class="flex items-center gap-4">
                <span>🕐 {{ $memo->created_at->format('Y年n月j日 H:i') }}</span>
                @if ($memo->updated_at->ne($memo->created_at))
                    <span>✏️ {{ $memo->updated_at->format('Y年n月j日 H:i') }} 更新</span>
                @endif
            </div>
        </div>
    </div>
</div>
