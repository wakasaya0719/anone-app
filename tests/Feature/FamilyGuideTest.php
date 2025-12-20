<?php

declare(strict_types=1);

use App\Models\User;

test('認証済みユーザーは家族への案内状PDFをダウンロードできる', function () {
    $user = User::factory()->create([
        'name' => 'テストユーザー',
        'email' => 'test@example.com',
    ]);

    $response = $this->actingAs($user)->get(route('family-guide.download'));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    // ファイル名にはUTF-8エンコーディングが含まれるため、部分一致で確認
    expect($response->headers->get('content-disposition'))->toContain('attachment');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
});

test('未認証ユーザーは家族への案内状PDFにアクセスできない', function () {
    $response = $this->get(route('family-guide.download'));

    $response->assertRedirect(route('login'));
});

test('家族設定ページに案内状ダウンロードボタンが表示される', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.family'));

    $response->assertSuccessful();
    $response->assertSee('家族への案内状');
    $response->assertSee('案内状をダウンロード');
    $response->assertSee('万が一の時に備えて');
});
