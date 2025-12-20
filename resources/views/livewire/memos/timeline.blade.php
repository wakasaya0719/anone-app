<?php

use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\title;

layout('components.layouts.app');
title('年表（タイムライン）');

// 選択中の受信者
state(['selectedRecipient' => null]);

// 受信者一覧を取得（投稿数付き）
$recipients = computed(function () {
    $memos = Auth::user()->memos()
        ->published()
        ->get();

    // 受信者別にグループ化し、投稿数をカウント
    $grouped = $memos->groupBy('recipient')->map(function ($items) {
        return [
            'count' => $items->count(),
            'latest_date' => $items->max('memo_date') ?? $items->max('created_at'),
        ];
    });

    return $grouped;
});

// 選択された受信者の投稿を年表順に取得
$timelineMemos = computed(function () {
    $query = Auth::user()->memos()
        ->published();

    // recipient が null の場合は、recipient が null のもののみ
    if ($this->selectedRecipient === null || $this->selectedRecipient === '') {
        $query->whereNull('recipient');
    } else {
        $query->where('recipient', $this->selectedRecipient);
    }

    // memo_date 優先、なければ created_at でソート（降順）
    return $query->orderByRaw('COALESCE(memo_date, DATE(created_at)) DESC')
        ->orderBy('created_at', 'desc')
        ->get();
});

// 受信者を選択
$selectRecipient = function (?string $recipient) {
    $this->selectedRecipient = $recipient;
};

?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">年表（タイムライン）</flux:heading>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            受信者別の投稿を時系列で確認できます
        </p>
    </div>

    {{-- 受信者選択タブ --}}
    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">受信者を選択</flux:heading>
        
        <div class="flex flex-wrap gap-3">
            {{-- 「未指定」タブ --}}
            <button
                wire:click="selectRecipient(null)"
                @class([
                    'rounded-lg border px-4 py-3 text-left transition',
                    'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' => $selectedRecipient === null,
                    'border-gray-200 bg-white hover:border-blue-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-600' => $selectedRecipient !== null,
                ])
            >
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                    未指定
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $this->recipients->get('') ? $this->recipients->get('')['count'] : 0 }}件
                </div>
            </button>
            
            {{-- 各受信者のタブ --}}
            @foreach($this->recipients->except([''])->keys()->filter() as $recipient)
                <button
                    wire:click="selectRecipient('{{ $recipient }}')"
                    @class([
                        'rounded-lg border px-4 py-3 text-left transition',
                        'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' => $selectedRecipient === $recipient,
                        'border-gray-200 bg-white hover:border-blue-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-600' => $selectedRecipient !== $recipient,
                    ])
                >
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                        🎁 {{ $recipient }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->recipients[$recipient]['count'] }}件
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- 年表表示エリア --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        @if($this->timelineMemos->count() > 0)
            <div class="relative space-y-8">
                {{-- タイムライン縦線 --}}
                <div class="absolute left-2.5 top-0 h-full w-0.5 bg-gradient-to-b from-blue-500 via-purple-500 to-pink-500"></div>
                
                @foreach($this->timelineMemos as $memo)
                    <div class="relative flex gap-4">
                        {{-- 左側: 青丸と日付 --}}
                        <div class="flex flex-col items-center" style="min-width: 80px;">
                            {{-- タイムライン丸印 --}}
                            <div class="relative z-10 h-5 w-5 rounded-full border-4 border-white bg-blue-500 shadow-md dark:border-gray-800"></div>
                            
                            {{-- 日付 --}}
                            <div class="mt-2 text-center">
                                <div class="text-xs font-bold text-gray-600 dark:text-gray-400">
                                    @if($memo->memo_date)
                                        {{ $memo->memo_date->format('Y年') }}
                                    @else
                                        {{ $memo->created_at->format('Y年') }}
                                    @endif
                                </div>
                                <div class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                    @if($memo->memo_date)
                                        {{ $memo->memo_date->format('n/j') }}
                                    @else
                                        {{ $memo->created_at->format('n/j') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        {{-- 右側: 投稿カード --}}
                        <div class="flex-1">
                            <a 
                                href="{{ route('memos.show', $memo) }}" 
                                wire:navigate
                                class="block rounded-lg border border-gray-200 bg-gray-50 p-4 transition hover:border-blue-300 hover:bg-blue-50 hover:shadow-md dark:border-gray-600 dark:bg-gray-700/50 dark:hover:border-blue-500 dark:hover:bg-gray-600"
                            >
                                {{-- タイトル --}}
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $memo->title }}
                                </h3>
                                
                                {{-- 本文プレビュー --}}
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ Str::limit($memo->content, 100) }}
                                </p>
                                
                                {{-- 感情タグ --}}
                                @if($memo->emotion_tag)
                                    <div class="mt-3 flex items-center gap-2">
                                        <span class="text-base">{{ $memo->emotion_tag->emoji() }}</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $memo->emotion_tag->label() }}
                                        </span>
                                    </div>
                                @endif
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon.document-text class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-4 text-gray-600 dark:text-gray-400">
                    @if($selectedRecipient)
                        「{{ $selectedRecipient }}」宛の投稿はまだありません
                    @else
                        受信者が未指定の投稿はありません
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
