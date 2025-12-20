<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Mpdf\Mpdf;

/**
 * 家族への案内状PDF生成コントローラー
 */
class FamilyGuideController extends Controller
{
    /**
     * 家族への案内状PDFを生成・ダウンロード
     */
    public function download(): Response
    {
        $user = auth()->user();

        // PDFデータを準備
        $data = [
            'userName' => $user->name,
            'userEmail' => $user->email,
            'appUrl' => config('app.url'),
            'appName' => config('app.name', '言伝（Aノne）'),
            'generatedDate' => now()->format('Y年m月d日'),
            'supportEmail' => config('mail.from.address', 'support@example.com'),
        ];

        // ビューをレンダリング
        $html = view('pdf.family-guide', $data)->render();

        // mPDFでPDF生成（日本語対応）
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);

        // ダウンロード
        $filename = '家族への案内状_'.$user->name.'_'.now()->format('Ymd').'.pdf';

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
