<?php

use function Livewire\Volt\{layout, mount, rules, state, title};
use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\Memo;

layout('layouts.app');

// 状態管理
state([
    'memo',
    'title' => '',
    'content' => '',
    'emotion_tag' => null,
    'memo_date' => null,
    'sender' => '',
    'recipient' => '',
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

<div class="space-y-6">
    {{-- ヘッダー --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">言伝編集</flux:heading>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                言伝の内容を編集します
            </p>
        </div>

        <div class="flex gap-2">
            <flux:button href="{{ route('memos.show', $memo) }}" wire:navigate variant="ghost" icon="arrow-left">
                詳細に戻る
            </flux:button>

            <flux:button href="{{ route('memos.index') }}" wire:navigate variant="ghost">
                一覧に戻る
            </flux:button>
        </div>
    </div>

    {{-- フォーム --}}
    <form wire:submit="update" class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            {{-- タイトル --}}
            <flux:field>
                <flux:label>タイトル <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="title" type="text" placeholder="例：今日の出来事" />
                <flux:error name="title" />
            </flux:field>

            {{-- 本文 --}}
            <flux:field>
                <flux:label>本文 <span class="text-red-500">*</span></flux:label>
                <flux:textarea wire:model="content" rows="10" placeholder="伝えたいことを書いてください...">{{ $content }}</flux:textarea>
                <flux:error name="content" />
                <flux:description>
                    残り: {{ 10000 - mb_strlen($content) }} 文字
                </flux:description>
            </flux:field>

            {{-- 感情タグ --}}
            <flux:field>
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
            <flux:field>
                <flux:label>投稿日（思い出の日付）</flux:label>
                <flux:input wire:model="memo_date" type="date" max="{{ today()->format('Y-m-d') }}" />
                <flux:error name="memo_date" />
                <flux:description>
                    この言伝に関連する日付（過去の日付のみ）
                </flux:description>
            </flux:field>

            {{-- 送信者・受信者 --}}
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>送信者（From）</flux:label>
                    <flux:input wire:model="sender" type="text" placeholder="例：お父さん" />
                    <flux:error name="sender" />
                </flux:field>

                <flux:field>
                    <flux:label>受信者（To）</flux:label>
                    <flux:input wire:model="recipient" type="text" placeholder="例：太郎" />
                    <flux:error name="recipient" />
                </flux:field>
            </div>

            {{-- ステータス --}}
            <flux:field>
                <flux:label>ステータス <span class="text-red-500">*</span></flux:label>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2">
                        <input type="radio" wire:model="status" value="{{ MemoStatus::DRAFT->value }}"
                            class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                            @disabled($memo->status === MemoStatus::PUBLISHED)>
                        <span>下書き</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" wire:model="status" value="{{ MemoStatus::PUBLISHED->value }}"
                            class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>公開</span>
                    </label>
                </div>
                <flux:error name="status" />
                @if ($memo->status === MemoStatus::PUBLISHED)
                    <flux:description>
                        公開済みの言伝を下書きに戻すことはできません。
                    </flux:description>
                @endif
            </flux:field>
        </div>

        {{-- アクションボタン --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span class="text-red-500">*</span> は必須項目です
            </div>

            <div class="flex gap-3">
                @if ($memo->status === MemoStatus::DRAFT)
                    <flux:button type="button" wire:click="saveDraft" variant="ghost">
                        下書き保存
                    </flux:button>

                    <flux:button type="button" wire:click="publish" variant="primary" icon="paper-airplane">
                        公開する
                    </flux:button>
                @else
                    <flux:button type="submit" variant="primary" icon="check">
                        更新する
                    </flux:button>
                @endif
            </div>
        </div>
    </form>
</div>
