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

            {{-- 統計情報（将来の拡張用） --}}
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 bg-gradient-to-br from-purple-50 to-purple-100 p-6 dark:border-neutral-700 dark:from-purple-900/20 dark:to-purple-800/20">
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <div class="rounded-full bg-purple-500 p-4 text-white">
                        <flux:icon.chart-bar class="h-8 w-8" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                        統計情報
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        近日公開
                    </p>
                </div>
            </div>
        </div>

        {{-- 最近の活動エリア（将来の拡張用） --}}
        <div class="relative flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-gray-800">
            <flux:heading size="lg" class="mb-4">最近の活動</flux:heading>
            <div class="flex h-full items-center justify-center">
                <div class="text-center">
                    <flux:icon.document-text class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        最近の言伝がここに表示されます
                    </p>
                    <flux:button href="{{ route('memos.create') }}" wire:navigate variant="primary" icon="plus" class="mt-4">
                        最初の言伝を作成
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
