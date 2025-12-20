<?php

use function Livewire\Volt\{computed, layout, state, title};
use App\Models\Memo;
use App\Enums\MemoStatus;
use App\Enums\EmotionTag;
use Illuminate\Support\Facades\Storage;

layout('components.layouts.app');
title('言伝一覧');

// 状態管理
state(['search' => '', 'recipient' => '', 'emotionTag' => '', 'status' => '']);

// 言伝一覧を取得（検索・フィルター対応）
$memos = computed(function () {
    return Memo::query()
        ->with('user.familyMembers')
        ->where('user_id', auth()->id())
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('content', 'like', "%{$this->search}%");
            });
        })
        ->when($this->recipient, function ($query) {
            $query->where('recipient', 'like', "%{$this->recipient}%");
        })
        ->when($this->emotionTag, function ($query) {
            $query->where('emotion_tag', $this->emotionTag);
        })
        ->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })
        ->latest('created_at')
        ->paginate(20);
});

// ユニークな受信者リストを取得
$recipients = computed(function () {
    return Memo::query()
        ->where('user_id', auth()->id())
        ->whereNotNull('recipient')
        ->where('recipient', '!=', '')
        ->distinct()
        ->pluck('recipient')
        ->sort()
        ->values();
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

<div class="space-y-8 animate-gentle-fade-in">
    {{-- ヘッダー - より柔らかいスタイル --}}
    <div class="flex items-center justify-between rounded-2xl bg-white/80 p-6 shadow-md backdrop-blur-sm dark:bg-soft-800/80">
        <div>
            <flux:heading size="xl" class="text-warmth-800 dark:text-warmth-200">
                📝 言伝一覧
            </flux:heading>
            <p class="mt-2 text-sm text-soft-600 dark:text-soft-400">
                心を込めた言葉を、未来へ届けましょう
            </p>
        </div>
        
        <flux:button 
            href="{{ route('memos.create') }}" 
            wire:navigate 
            variant="primary" 
            icon="plus"
            class="bg-gradient-to-r from-warmth-500 to-coral-500 hover:from-warmth-600 hover:to-coral-600 shadow-md"
        >
            新しい言伝を作る
        </flux:button>
    </div>

    {{-- 成功メッセージ - より優しく --}}
    @if (session('message'))
        <div class="rounded-2xl border-2 border-coral-300 bg-gradient-to-r from-coral-50 to-warmth-50 p-4 shadow-sm dark:border-coral-700 dark:from-soft-800 dark:to-soft-700">
            <div class="flex items-center gap-3">
                <span class="text-2xl">✨</span>
                <p class="text-coral-800 dark:text-coral-200">{{ session('message') }}</p>
            </div>
        </div>
    @endif

    {{-- 検索・フィルター - カードスタイルに --}}
    <div class="rounded-2xl bg-white/90 p-6 shadow-md backdrop-blur-sm dark:bg-soft-800/90">
        <div class="flex gap-4">
            {{-- フリー検索（広め） --}}
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="タイトルや本文で検索..."
                    icon="magnifying-glass"
                    class="rounded-xl"
                />
            </div>

            {{-- 受信者フィルター（狭め） --}}
            <div class="w-48">
                <flux:select wire:model.live="recipient" placeholder="すべての受信者" class="rounded-xl">
                    <option value="">すべての受信者</option>
                    @foreach ($this->recipients as $recipientOption)
                        <option value="{{ $recipientOption }}">{{ $recipientOption }}</option>
                    @endforeach
                </flux:select>
            </div>

            {{-- 感情タグフィルター（狭め） --}}
            <div class="w-48">
                <flux:select wire:model.live="emotionTag" placeholder="すべての感情" class="rounded-xl">
                    <option value="">すべての感情</option>
                    @foreach (EmotionTag::cases() as $tag)
                        <option value="{{ $tag->value }}">{{ $tag->emoji() }} {{ $tag->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            {{-- ステータスフィルター（狭め） --}}
            <div class="w-48">
                <flux:select wire:model.live="status" placeholder="すべてのステータス" class="rounded-xl">
                    <option value="">すべてのステータス</option>
                    @foreach (MemoStatus::cases() as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </div>

    {{-- 言伝一覧 - より温かみのあるカードデザイン --}}
    @if ($this->memos->count() > 0)
        <div class="space-y-6">
            @foreach ($this->memos as $memo)
                <article class="group memo-card">
                    <div class="flex items-start gap-4">
                        {{-- 添付写真サムネイル --}}
                        @if ($memo->photo_path)
                            <div class="flex-shrink-0">
                                <img src="{{ Storage::url($memo->photo_path) }}" 
                                    alt="添付写真"
                                    class="h-24 w-24 rounded-xl object-cover ring-2 ring-warmth-200 dark:ring-soft-600 shadow-sm">
                            </div>
                        @endif
                        
                        <div class="flex-1">
                            {{-- タイトル --}}
                            <h3 class="text-xl font-bold text-warmth-800 dark:text-warmth-200">
                                <a 
                                    href="{{ route('memos.show', $memo) }}" 
                                    wire:navigate 
                                    class="hover:text-warmth-600 dark:hover:text-warmth-400 transition-colors"
                                >
                                    {{ $memo->title }}
                                </a>
                            </h3>

                            {{-- メタ情報 --}}
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                {{-- ステータス --}}
                                <flux:badge :variant="$this->getStatusColor($memo->status->value)" size="sm">
                                    {{ $this->getStatusLabel($memo->status->value) }}
                                </flux:badge>

                                {{-- 感情タグ --}}
                                @if ($memo->emotion_tag)
                                    <span class="emotion-tag">
                                        <span class="text-lg">{{ $memo->emotion_tag->emoji() }}</span>
                                        <span>{{ $memo->emotion_tag->label() }}</span>
                                    </span>
                                @endif

                                {{-- 日付 --}}
                                @if ($memo->memo_date)
                                    <span class="flex items-center gap-1.5 text-sm text-soft-600 dark:text-soft-400">
                                        <flux:icon.calendar variant="micro" />
                                        {{ $memo->memo_date->format('Y年n月j日') }}
                                    </span>
                                @endif
                            </div>

                            {{-- 本文プレビュー --}}
                            <p class="mt-4 line-clamp-3 leading-relaxed text-soft-700 dark:text-soft-300">
                                {{ Str::limit($memo->content, 150) }}
                            </p>

                            {{-- 送受信者情報 --}}
                            @if ($memo->sender || $memo->recipient)
                                <div class="mt-4 flex gap-6 text-sm">
                                    @if ($memo->sender)
                                        <span class="flex items-center gap-2 text-warmth-700 dark:text-warmth-300">
                                            <span>💝</span>
                                            <span>{{ $memo->sender }}より</span>
                                        </span>
                                    @endif
                                    @if ($memo->recipient)
                                        <span class="flex items-center gap-2 text-coral-700 dark:text-coral-300">
                                            <span>🎁</span>
                                            <span>
                                                {{ $memo->recipient }}へ
                                                @if ($memo->recipient_age)
                                                    <span class="ml-1 inline-flex items-center rounded-full bg-coral-100 px-2 py-0.5 text-xs font-medium text-coral-800 dark:bg-coral-900/30 dark:text-coral-200">
                                                        {{ $memo->recipient_age }}歳
                                                    </span>
                                                @endif
                                            </span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- アクションボタン --}}
                        <div class="ml-6 flex flex-col gap-2 flex-shrink-0">
                            <flux:button
                                href="{{ route('memos.show', $memo) }}"
                                wire:navigate
                                variant="ghost"
                                size="sm"
                                icon="eye"
                                class="hover:bg-warmth-100 dark:hover:bg-soft-700"
                            >
                                詳細
                            </flux:button>

                            <flux:button
                                href="{{ route('memos.edit', $memo) }}"
                                wire:navigate
                                variant="ghost"
                                size="sm"
                                icon="pencil"
                                class="hover:bg-warmth-100 dark:hover:bg-soft-700"
                            >
                                編集
                            </flux:button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- ページネーション --}}
        <div class="mt-8">
            {{ $this->memos->links() }}
        </div>
    @else
        {{-- 空状態 - より温かみのある表現 --}}
        <div class="rounded-2xl border-2 border-dashed border-warmth-300 bg-gradient-to-br from-warmth-50 to-coral-50 p-16 text-center dark:border-soft-600 dark:from-soft-800 dark:to-soft-700">
            <div class="mx-auto max-w-md">
                <span class="text-6xl">📮</span>
                <h3 class="mt-6 text-xl font-bold text-warmth-800 dark:text-warmth-200">
                    @if ($search || $recipient || $status || $emotionTag)
                        見つかりませんでした
                    @else
                        まだ言伝がありません
                    @endif
                </h3>
                <p class="mt-3 leading-relaxed text-soft-600 dark:text-soft-400">
                    @if ($search || $recipient || $status || $emotionTag)
                        検索条件を変えて、もう一度お試しください。
                    @else
                        心に残る言葉を、未来の大切な人へ届けましょう。<br>
                        最初の言伝を作成してみませんか？
                    @endif
                </p>
                @if (!$search && !$recipient && !$status && !$emotionTag)
                    <flux:button 
                        href="{{ route('memos.create') }}" 
                        wire:navigate 
                        variant="primary" 
                        icon="plus" 
                        class="mt-6 bg-gradient-to-r from-warmth-500 to-coral-500 shadow-md"
                    >
                        最初の言伝を作る
                    </flux:button>
                @endif
            </div>
        </div>
    @endif
</div>
