<?php

use function Livewire\Volt\{layout, mount, state, title};
use App\Models\Memo;

layout('components.layouts.app');

// 状態管理
state(['memo']);

// 初期化
mount(function (Memo $memo) {
    $this->authorize('view', $memo);
    $this->memo = $memo;
    $this->title = $memo->title;
});

// 言伝削除
$delete = function () {
    $this->authorize('delete', $this->memo);
    
    $this->memo->delete();
    
    return $this->redirect(route('memos.index'), navigate: true);
};

?>

<div class="space-y-6">
    {{-- ヘッダー --}}
    <div class="flex items-center justify-between">
        <flux:button href="{{ route('memos.index') }}" wire:navigate variant="ghost" icon="arrow-left">
            一覧に戻る
        </flux:button>

        <div class="flex gap-2">
            <flux:button href="{{ route('memos.edit', $memo) }}" wire:navigate variant="primary" icon="pencil">
                編集
            </flux:button>

            <flux:button
                wire:click="delete"
                wire:confirm="本当に削除しますか？この操作は取り消せません。"
                variant="danger"
                icon="trash"
            >
                削除
            </flux:button>
        </div>
    </div>

    {{-- メインコンテンツ --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        {{-- ヘッダー部分 --}}
        <div class="border-b border-gray-200 p-6 dark:border-gray-700">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $memo->title }}
                    </h1>

                    {{-- メタ情報 --}}
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        {{-- ステータス --}}
                        <flux:badge :variant="$memo->status->color()" size="sm">
                            {{ $memo->status->label() }}
                        </flux:badge>

                        {{-- 感情タグ --}}
                        @if ($memo->emotion_tag)
                            <div class="flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-700">
                                <span class="text-lg">{{ $memo->emotion_tag->emoji() }}</span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $memo->emotion_tag->label() }}
                                </span>
                            </div>
                        @endif

                        {{-- 日付 --}}
                        @if ($memo->memo_date)
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <flux:icon.calendar variant="micro" />
                                <span>{{ $memo->memo_date->format('Y年n月j日') }}</span>
                            </div>
                        @endif

                        {{-- 公開日時 --}}
                        @if ($memo->published_at)
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <flux:icon.globe-alt variant="micro" />
                                <span>{{ $memo->published_at->format('Y年n月j日 H:i') }} 公開</span>
                            </div>
                        @endif

                        {{-- 作成日時 --}}
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <flux:icon.clock variant="micro" />
                            <span>{{ $memo->created_at->format('Y年n月j日 H:i') }} 作成</span>
                        </div>

                        {{-- 更新日時 --}}
                        @if ($memo->updated_at->ne($memo->created_at))
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <flux:icon.pencil variant="micro" />
                                <span>{{ $memo->updated_at->format('Y年n月j日 H:i') }} 更新</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 送受信者情報 --}}
        @if ($memo->sender || $memo->recipient)
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="grid gap-4 md:grid-cols-2">
                    @if ($memo->sender)
                        <div>
                            <flux:heading size="sm" class="text-gray-600 dark:text-gray-400">送信者</flux:heading>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ $memo->sender }}</p>
                        </div>
                    @endif

                    @if ($memo->recipient)
                        <div>
                            <flux:heading size="sm" class="text-gray-600 dark:text-gray-400">受信者</flux:heading>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ $memo->recipient }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- 本文 --}}
        <div class="p-6">
            <flux:heading size="sm" class="mb-4 text-gray-600 dark:text-gray-400">本文</flux:heading>
            <div class="prose prose-gray max-w-none dark:prose-invert">
                <p class="whitespace-pre-wrap text-gray-900 dark:text-white">{{ $memo->content }}</p>
            </div>
        </div>
    </div>

    {{-- フッター情報 --}}
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
        <div class="flex items-center justify-between">
            <div>
                投稿者: <span class="font-semibold text-gray-900 dark:text-white">{{ $memo->user->name }}</span>
            </div>
            <div>
                ID: {{ $memo->id }}
            </div>
        </div>
    </div>
</div>
