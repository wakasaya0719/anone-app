<?php

use function Livewire\Volt\{layout, mount, rules, state, title};
use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\Memo;

layout('components.layouts.app');

// 状態管理
state([
    'memo',
    'title' => '',
    'content' => '',
    'emotion_tag' => null,
    'memo_date' => null,
    'sender' => '',
    'recipient' => '',
    'recipient_age' => null,
    'status' => '',
]);

// 初期化
mount(function (Memo $memo) {
    $this->authorize('update', $memo);
    
    $this->memo = $memo;
    $this->title = $memo->title;
    $this->content = $memo->content;
    $this->emotion_tag = $memo->emotion_tag?->value;
    $this->memo_date = $memo->memo_date?->format('Y-m-d');
    $this->sender = $memo->sender ?? '';
    $this->recipient = $memo->recipient ?? '';
    $this->recipient_age = $memo->recipient_age;
    $this->status = $memo->status->value;
});

// バリデーションルール
rules([
    'title' => 'required|string|max:255|regex:/\S/',
    'content' => 'required|string|max:10000|regex:/\S/',
    'emotion_tag' => 'nullable|string',
    'memo_date' => 'nullable|date|before_or_equal:today',
    'sender' => 'nullable|string|max:100',
    'recipient' => 'nullable|string|max:100',
    'recipient_age' => 'nullable|integer|min:0|max:150',
    'status' => 'required|string',
]);

// 言伝更新
$update = function () {
    $this->authorize('update', $this->memo);
    
    $validated = $this->validate();
    
    // 公開済み→下書きへの変更を防ぐ
    if ($this->memo->status === MemoStatus::PUBLISHED && $validated['status'] === MemoStatus::DRAFT->value) {
        $this->addError('status', '公開済みの言伝を下書きに戻すことはできません。');
        return;
    }
    
    // 下書き→公開の場合は公開日時を設定
    $publishedAt = $this->memo->published_at;
    if ($this->memo->status === MemoStatus::DRAFT && $validated['status'] === MemoStatus::PUBLISHED->value) {
        $publishedAt = now();
    }
    
    $this->memo->update([
        'title' => $validated['title'],
        'content' => $validated['content'],
        'emotion_tag' => $validated['emotion_tag'] ?: null,
        'memo_date' => $validated['memo_date'] ?: null,
        'sender' => $validated['sender'] ?: null,
        'recipient' => $validated['recipient'] ?: null,
        'recipient_age' => $validated['recipient_age'] ?: null,
        'status' => $validated['status'],
        'published_at' => $publishedAt,
    ]);
    
    session()->flash('message', '言伝を更新しました。');
    
    return $this->redirect(route('memos.show', $this->memo), navigate: true);
};

// 下書きとして保存
$saveDraft = function () {
    $this->status = MemoStatus::DRAFT->value;
    $this->update();
};

// 公開
$publish = function () {
    $this->status = MemoStatus::PUBLISHED->value;
    $this->update();
};

?>

<div class="space-y-8 animate-gentle-fade-in">
    {{-- ヘッダー --}}
    <div class="flex items-center justify-between rounded-2xl bg-white/80 p-6 shadow-md backdrop-blur-sm dark:bg-soft-800/80">
        <div>
            <flux:heading size="xl" class="text-warmth-800 dark:text-warmth-200">
                ✏️ 言伝編集
            </flux:heading>
            <p class="mt-2 text-sm text-soft-600 dark:text-soft-400">
                大切な言葉を磨きましょう
            </p>
        </div>

        <div class="flex gap-2">
            <flux:button 
                href="{{ route('memos.show', $memo) }}" 
                wire:navigate 
                variant="ghost" 
                icon="arrow-left"
                class="hover:bg-warmth-100 dark:hover:bg-soft-700"
            >
                詳細に戻る
            </flux:button>

            <flux:button 
                href="{{ route('memos.index') }}" 
                wire:navigate 
                variant="ghost"
                class="hover:bg-warmth-100 dark:hover:bg-soft-700"
            >
                一覧に戻る
            </flux:button>
        </div>
    </div>

    {{-- フォーム --}}
    <form wire:submit="update" class="space-y-8">
        <div class="rounded-2xl border border-warmth-200 bg-white p-8 shadow-md dark:border-soft-700 dark:bg-soft-800">
            {{-- タイトル --}}
            <flux:field class="space-y-2">
                <flux:label>タイトル <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="title" type="text" placeholder="例：今日の出来事" />
                <flux:error name="title" />
            </flux:field>

            {{-- 本文 --}}
            <flux:field class="space-y-2">
                <flux:label>本文 <span class="text-red-500">*</span></flux:label>
                <flux:textarea wire:model="content" rows="10" placeholder="伝えたいことを書いてください...">{{ $content }}</flux:textarea>
                <flux:error name="content" />
                <flux:description>
                    残り: {{ 10000 - mb_strlen($content) }} 文字
                </flux:description>
            </flux:field>

            {{-- 感情タグ --}}
            <flux:field class="space-y-2">
                <flux:label>感情タグ</flux:label>
                <flux:select wire:model="emotion_tag" placeholder="感情を選択...">
                    <option value="">なし</option>
                    @foreach (EmotionTag::cases() as $tag)
                        <option value="{{ $tag->value }}">{{ $tag->emoji() }} {{ $tag->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="emotion_tag" />
            </flux:field>

            {{-- 投稿日 --}}
            <flux:field class="space-y-2">
                <flux:label>投稿日（思い出の日付）</flux:label>
                <flux:input wire:model="memo_date" type="date" max="{{ today()->format('Y-m-d') }}" />
                <flux:error name="memo_date" />
            </flux:field>

            {{-- 送信者・受信者・年齢 --}}
            <div class="grid gap-4 md:grid-cols-3">
                <flux:field class="space-y-2">
                    <flux:label>送信者（From）</flux:label>
                    <flux:input wire:model="sender" type="text" placeholder="例：お父さん" />
                    <flux:error name="sender" />
                </flux:field>

                <flux:field class="space-y-2">
                    <flux:label>受信者（To）</flux:label>
                    <flux:input wire:model="recipient" type="text" placeholder="例：太郎" />
                    <flux:error name="recipient" />
                </flux:field>

                <flux:field class="space-y-2">
                    <flux:label>年齢</flux:label>
                    <flux:input wire:model="recipient_age" type="number" min="0" max="150" placeholder="例：5" />
                    <flux:error name="recipient_age" />
                </flux:field>
            </div>

            {{-- ステータス --}}
            <flux:field class="space-y-2">
                <flux:label>ステータス <span class="text-coral-500">✱</span></flux:label>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2">
                        <input type="radio" wire:model="status" value="{{ MemoStatus::DRAFT->value }}"
                            class="h-4 w-4 border-warmth-300 text-warmth-600 focus:ring-warmth-500"
                            @disabled($memo->status === MemoStatus::PUBLISHED)>
                        <span class="text-soft-700 dark:text-soft-300">📄 下書き</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" wire:model="status" value="{{ MemoStatus::PUBLISHED->value }}"
                            class="h-4 w-4 border-warmth-300 text-warmth-600 focus:ring-warmth-500">
                        <span class="text-soft-700 dark:text-soft-300">🌟 公開</span>
                    </label>
                </div>
                <flux:error name="status" />
                @if ($memo->status === MemoStatus::PUBLISHED)
                    <flux:description class="text-coral-600 dark:text-coral-400">
                        💡 公開済みの言伝を下書きに戻すことはできません。
                    </flux:description>
                @endif
            </flux:field>
        </div>

        {{-- アクションボタン --}}
        <div class="flex items-center justify-between rounded-2xl border border-warmth-200 bg-gradient-to-r from-warmth-50 to-coral-50 p-6 shadow-sm dark:border-soft-700 dark:from-soft-800 dark:to-soft-700">
            <div class="flex items-center gap-2 text-sm text-soft-600 dark:text-soft-400">
                <span class="text-coral-500">✱</span>
                <span>は必須項目です</span>
            </div>

            <div class="flex gap-3">
                @if ($memo->status === MemoStatus::DRAFT)
                    <flux:button 
                        type="button" 
                        wire:click="saveDraft" 
                        variant="ghost"
                        class="hover:bg-warmth-100 dark:hover:bg-soft-700"
                    >
                        📄 下書き保存
                    </flux:button>

                    <flux:button 
                        type="button" 
                        wire:click="publish" 
                        variant="primary" 
                        icon="paper-airplane"
                        class="bg-gradient-to-r from-warmth-500 to-coral-500 hover:from-warmth-600 hover:to-coral-600 shadow-md"
                    >
                        公開する
                    </flux:button>
                @else
                    <flux:button 
                        type="submit" 
                        variant="primary" 
                        icon="check"
                        class="bg-gradient-to-r from-warmth-500 to-coral-500 hover:from-warmth-600 hover:to-coral-600 shadow-md"
                    >
                        更新する
                    </flux:button>
                @endif
            </div>
        </div>
    </form>
</div>
