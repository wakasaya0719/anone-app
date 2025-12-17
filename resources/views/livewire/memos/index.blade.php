<?php

use function Livewire\Volt\{computed, layout, state, title};
use App\Models\Memo;
use App\Enums\MemoStatus;
use App\Enums\EmotionTag;

layout('components.layouts.app');
title('言伝一覧');

// 状態管理
state(['search' => '', 'status' => '', 'emotionTag' => '']);

// 言伝一覧を取得（検索・フィルター対応）
$memos = computed(function () {
    return Memo::query()
        ->where('user_id', auth()->id())
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('content', 'like', "%{$this->search}%");
            });
        })
        ->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })
        ->when($this->emotionTag, function ($query) {
            $query->where('emotion_tag', $this->emotionTag);
        })
        ->latest('created_at')
        ->paginate(20);
});

// ステータスラベル取得
$getStatusLabel = function (string $status): string {
    return MemoStatus::from($status)->label();
};

// ステータスカラー取得
$getStatusColor = function (string $status): string {
    return MemoStatus::from($status)->color();
};

// 感情タグラベル取得
$getEmotionLabel = function (?string $emotionTag): string {
    if (!$emotionTag) {
        return '';
    }
    $tag = EmotionTag::from($emotionTag);
    return $tag->emoji() . ' ' . $tag->label();
};

// 言伝削除
$delete = function (Memo $memo) {
    $this->authorize('delete', $memo);
    
    $memo->delete();
    
    session()->flash('message', '言伝を削除しました。');
};

?>

<div class="space-y-6">
    {{-- ヘッダー --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">言伝一覧</flux:heading>
        
        <flux:button href="{{ route('memos.create') }}" wire:navigate variant="primary" icon="plus">
            新規作成
        </flux:button>
    </div>

    {{-- 成功メッセージ --}}
    @if (session('message'))
        <flux:callout variant="success">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- 検索・フィルター --}}
    <div class="grid gap-4 md:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="タイトルや本文で検索..."
            icon="magnifying-glass"
        />

        <flux:select wire:model.live="status" placeholder="すべてのステータス">
            <option value="">すべてのステータス</option>
            @foreach (MemoStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="emotionTag" placeholder="すべての感情タグ">
            <option value="">すべての感情タグ</option>
            @foreach (EmotionTag::cases() as $tag)
                <option value="{{ $tag->value }}">{{ $tag->emoji() }} {{ $tag->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- 言伝一覧 --}}
    @if ($this->memos->count() > 0)
        <div class="space-y-4">
            @foreach ($this->memos as $memo)
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            {{-- タイトル --}}
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <a href="{{ route('memos.show', $memo) }}" wire:navigate class="hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ $memo->title }}
                                </a>
                            </h3>

                            {{-- メタ情報 --}}
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                {{-- ステータス --}}
                                <flux:badge :variant="$this->getStatusColor($memo->status->value)" size="sm">
                                    {{ $this->getStatusLabel($memo->status->value) }}
                                </flux:badge>

                                {{-- 感情タグ --}}
                                @if ($memo->emotion_tag)
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs dark:bg-gray-700">
                                        {{ $this->getEmotionLabel($memo->emotion_tag?->value) }}
                                    </span>
                                @endif

                                {{-- 日付 --}}
                                @if ($memo->memo_date)
                                    <span class="flex items-center gap-1">
                                        <flux:icon.calendar variant="micro" />
                                        {{ $memo->memo_date->format('Y年n月j日') }}
                                    </span>
                                @endif

                                {{-- 作成日時 --}}
                                <span class="flex items-center gap-1">
                                    <flux:icon.clock variant="micro" />
                                    {{ $memo->created_at->format('Y/m/d H:i') }}
                                </span>
                            </div>

                            {{-- 本文プレビュー --}}
                            <p class="mt-3 line-clamp-2 text-gray-700 dark:text-gray-300">
                                {{ Str::limit($memo->content, 120) }}
                            </p>

                            {{-- 送受信者情報 --}}
                            @if ($memo->sender || $memo->recipient)
                                <div class="mt-2 flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    @if ($memo->sender)
                                        <span>From: {{ $memo->sender }}</span>
                                    @endif
                                    @if ($memo->recipient)
                                        <span>To: {{ $memo->recipient }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- アクションボタン --}}
                        <div class="ml-4 flex gap-2">
                            <flux:button
                                href="{{ route('memos.show', $memo) }}"
                                wire:navigate
                                variant="ghost"
                                size="sm"
                                icon="eye"
                            >
                                詳細
                            </flux:button>

                            <flux:button
                                href="{{ route('memos.edit', $memo) }}"
                                wire:navigate
                                variant="ghost"
                                size="sm"
                                icon="pencil"
                            >
                                編集
                            </flux:button>

                            <flux:button
                                wire:click="delete({{ $memo->id }})"
                                wire:confirm="本当に削除しますか？この操作は取り消せません。"
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                class="text-red-600 hover:text-red-700 dark:text-red-400"
                            >
                                削除
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ページネーション --}}
        <div class="mt-6">
            {{ $this->memos->links() }}
        </div>
    @else
        {{-- 空状態 --}}
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
            <flux:icon.document-text class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">言伝がありません</h3>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                @if ($search || $status || $emotionTag)
                    検索条件に一致する言伝が見つかりませんでした。
                @else
                    最初の言伝を作成しましょう。
                @endif
            </p>
            @if (!$search && !$status && !$emotionTag)
                <flux:button href="{{ route('memos.create') }}" wire:navigate variant="primary" icon="plus" class="mt-4">
                    新規作成
                </flux:button>
            @endif
        </div>
    @endif
</div>
