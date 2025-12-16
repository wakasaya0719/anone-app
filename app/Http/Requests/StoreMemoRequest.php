<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 言伝作成リクエスト
 */
class StoreMemoRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを取得
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'content' => ['required', 'string', 'max:10000', 'regex:/\S/'],
            'emotion_tag' => ['nullable', Rule::enum(EmotionTag::class)],
            'memo_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sender' => ['nullable', 'string', 'max:100'],
            'recipient' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::enum(MemoStatus::class)],
        ];
    }

    /**
     * カスタムエラーメッセージを取得
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'title.regex' => 'タイトルに空白以外の文字を入力してください。',
            'content.required' => '本文は必須です。',
            'content.max' => '本文は10000文字以内で入力してください。',
            'content.regex' => '本文に空白以外の文字を入力してください。',
            'emotion_tag' => '有効な感情タグを選択してください。',
            'memo_date.date' => '投稿日は有効な日付を指定してください。',
            'memo_date.before_or_equal' => '投稿日は今日以前の日付を指定してください。',
            'sender.max' => '送信者名は100文字以内で入力してください。',
            'recipient.max' => '受信者名は100文字以内で入力してください。',
            'status.required' => 'ステータスは必須です。',
            'status' => '有効なステータスを選択してください。',
        ];
    }

    /**
     * バリデーション後の処理
     */
    protected function prepareForValidation(): void
    {
        // ステータスが指定されていない場合はデフォルトで下書きに設定
        if (! $this->has('status')) {
            $this->merge([
                'status' => MemoStatus::DRAFT->value,
            ]);
        }

        // 公開ステータスの場合は公開日時を設定
        if ($this->status === MemoStatus::PUBLISHED->value && ! $this->has('published_at')) {
            $this->merge([
                'published_at' => now(),
            ]);
        }
    }
}
