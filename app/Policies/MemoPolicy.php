<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Memo;
use App\Models\User;

/**
 * 言伝の認可ポリシー
 *
 * 自分が作成した言伝のみ操作可能
 * 閲覧は作成者または共有された受信者のみ（フェーズ2）
 */
class MemoPolicy
{
    /**
     * ユーザーが言伝一覧を閲覧できるか
     *
     * すべての認証済みユーザーが自分の言伝を閲覧可能
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが特定の言伝を閲覧できるか
     *
     * 作成者のみ閲覧可能（フェーズ2で受信者も追加）
     */
    public function view(User $user, Memo $memo): bool
    {
        return $user->id === $memo->user_id;
    }

    /**
     * ユーザーが言伝を作成できるか
     *
     * すべての認証済みユーザーが作成可能
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが言伝を更新できるか
     *
     * 作成者のみ更新可能
     */
    public function update(User $user, Memo $memo): bool
    {
        return $user->id === $memo->user_id;
    }

    /**
     * ユーザーが言伝を削除できるか
     *
     * 作成者のみ削除可能
     */
    public function delete(User $user, Memo $memo): bool
    {
        return $user->id === $memo->user_id;
    }

    /**
     * ユーザーが削除した言伝を復元できるか
     *
     * 作成者のみ復元可能
     */
    public function restore(User $user, Memo $memo): bool
    {
        return $user->id === $memo->user_id;
    }

    /**
     * ユーザーが言伝を完全削除できるか
     *
     * 作成者のみ完全削除可能
     */
    public function forceDelete(User $user, Memo $memo): bool
    {
        return $user->id === $memo->user_id;
    }
}
