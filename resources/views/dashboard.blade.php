<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        {{-- ウェルカムメッセージ --}}
        <div>
            <flux:heading size="xl">ダッシュボード</flux:heading>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                こんにちは、{{ auth()->user()->name }}さん
            </p>
        </div>

        {{-- クイックアクションカード --}}
        <div class="grid gap-4 md:grid-cols-3">
            {{-- 言伝一覧へのアクセス --}}
            <a href="{{ route('memos.index') }}" wire:navigate
               class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 bg-gradient-to-br from-blue-50 to-blue-100 p-6 transition hover:shadow-lg dark:border-neutral-700 dark:from-blue-900/20 dark:to-blue-800/20">
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <div class="rounded-full bg-blue-500 p-4 text-white transition group-hover:scale-110">
                        <flux:icon.document-text class="h-8 w-8" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                        言伝一覧
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        すべての言伝を見る
                    </p>
                </div>
            </a>

            {{-- 新規作成 --}}
            <a href="{{ route('memos.create') }}" wire:navigate
               class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 bg-gradient-to-br from-green-50 to-green-100 p-6 transition hover:shadow-lg dark:border-neutral-700 dark:from-green-900/20 dark:to-green-800/20">
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <div class="rounded-full bg-green-500 p-4 text-white transition group-hover:scale-110">
                        <flux:icon.plus class="h-8 w-8" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                        新規作成
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        新しい言伝を作成
                    </p>
                </div>
            </a>

            {{-- 年表機能 --}}
            <a href="{{ route('memos.timeline') }}" wire:navigate
               class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 bg-gradient-to-br from-purple-50 to-purple-100 p-6 transition hover:shadow-lg dark:border-neutral-700 dark:from-purple-900/20 dark:to-purple-800/20">
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <div class="rounded-full bg-purple-500 p-4 text-white transition group-hover:scale-110">
                        <flux:icon.clock class="h-8 w-8" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                        年表
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        受信者別のタイムライン
                    </p>
                </div>
            </a>
        </div>

        {{-- 最近の言伝 --}}
        <div class="relative flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">最近の言伝</flux:heading>
                <flux:button 
                    href="{{ route('memos.index') }}" 
                    wire:navigate 
                    variant="ghost" 
                    size="sm"
                >
                    すべて見る
                </flux:button>
            </div>

            @php
                $recentMemos = Auth::user()->memos()->latest('created_at')->take(5)->get();
            @endphp

            @if ($recentMemos->count() > 0)
                <div class="space-y-3">
                    @foreach ($recentMemos as $memo)
                        <a 
                            href="{{ route('memos.show', $memo) }}" 
                            wire:navigate
                            class="block rounded-xl border border-gray-200 bg-gray-50 p-4 transition-all hover:border-blue-300 hover:bg-blue-50 hover:shadow-md dark:border-gray-700 dark:bg-gray-700/50 dark:hover:border-blue-600 dark:hover:bg-gray-600"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    {{-- タイトル --}}
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ Str::limit($memo->title, 40) }}
                                    </h4>
                                    
                                    {{-- 感情タグ・受信者・相対時刻 --}}
                                    <div class="mt-2 flex items-center gap-3 text-sm">
                                        @if ($memo->emotion_tag)
                                            <span class="flex items-center gap-1">
                                                <span class="text-base">{{ $memo->emotion_tag->emoji() }}</span>
                                                <span class="text-gray-600 dark:text-gray-400">{{ $memo->emotion_tag->label() }}</span>
                                            </span>
                                        @endif
                                        
                                        @if ($memo->recipient)
                                            <span class="flex items-center gap-1 text-blue-600 dark:text-blue-400">
                                                <span>🎁</span>
                                                <span>{{ $memo->recipient }}へ</span>
                                            </span>
                                        @endif
                                        
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $memo->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                                <flux:icon.chevron-right variant="micro" class="h-5 w-5 text-gray-400" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <flux:icon.document-text class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        まだ言伝がありません
                    </p>
                    <flux:button 
                        href="{{ route('memos.create') }}" 
                        wire:navigate 
                        variant="primary" 
                        icon="plus" 
                        class="mt-4"
                    >
                        最初の言伝を作成
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
