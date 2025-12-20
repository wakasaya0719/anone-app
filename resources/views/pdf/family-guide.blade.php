<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: 'ipagp', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        
        h1 {
            font-size: 20pt;
            margin: 0 0 10px 0;
        }
        
        h2 {
            font-size: 15pt;
            margin: 20px 0 10px 0;
            padding-left: 10px;
            border-left: 4px solid #000;
        }
        
        .box {
            background-color: #f5f5f5;
            border: 1px solid #999;
            padding: 12px;
            margin: 12px 0;
        }
        
        .box-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .info-row {
            margin: 6px 0;
        }
        
        .step {
            margin: 8px 0 8px 25px;
        }
        
        .important {
            background-color: #ffe;
            border: 2px solid #fa0;
            padding: 12px;
            margin: 12px 0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #999;
            text-align: center;
            font-size: 10pt;
        }
        
        ul {
            margin: 8px 0;
            padding-left: 20px;
        }
        
        li {
            margin: 4px 0;
        }
        
        .qr-area {
            border: 2px dashed #666;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
        }
        
        .qr-box {
            width: 100px;
            height: 100px;
            margin: 10px auto;
            border: 2px solid #999;
            background-color: #f9f9f9;
        }
        
        p {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>家族への案内状</h1>
        <p>このアプリには私の大切な記録があります</p>
    </div>

    <p style="text-align: center; margin: 15px 0;">
        この案内状をご覧になっているということは、<br>
        私に何かがあったのだと思います。<br>
        このアプリには、あなたへの想いが詰まっています。
    </p>

    <h2>このアプリについて</h2>
    <div class="box">
        <div class="box-title">{{ $appName }}</div>
        <p>
            このアプリは、日々の想いや記録を未来へ届けるために作られた言伝（ことづて）アプリです。
            私が書き綴った日記、メモ、家族への想いが保存されています。
        </p>
        <div class="info-row"><strong>アカウント登録者:</strong> {{ $userName }}</div>
        <div class="info-row"><strong>メールアドレス:</strong> {{ $userEmail }}</div>
    </div>

    <h2>アクセス方法</h2>
    <div class="box">
        <div class="box-title">スマートフォン・パソコンから</div>
        <div class="step"><strong>1.</strong> 以下のURLにアクセスしてください<br>
            <strong>{{ $appUrl }}</strong>
        </div>
        <div class="step"><strong>2.</strong> ログイン画面で「パスワードをお忘れですか？」をクリック</div>
        <div class="step"><strong>3.</strong> メールアドレス「{{ $userEmail }}」を入力</div>
        <div class="step"><strong>4.</strong> パスワードリセットのメールが届きます<br>
            <small>※ メールが届かない場合は、迷惑メールフォルダをご確認ください</small>
        </div>
        <div class="step"><strong>5.</strong> 新しいパスワードを設定してログインしてください</div>
    </div>

    <div class="qr-area">
        <p><strong>QRコードでアクセス</strong></p>
        <div class="qr-box"></div>
        <p style="font-size: 10pt;">
            ※ QRコード生成サービスで下記URLのQRコードを作成し、ここに貼り付けてください
        </p>
        <p><strong>{{ $appUrl }}/login</strong></p>
    </div>

    <h2>重要な注意事項</h2>
    <div class="important">
        <p><strong>パスワードリセットには以下が必要です：</strong></p>
        <ul>
            <li>登録メールアドレス（{{ $userEmail }}）にアクセスできること</li>
            <li>メールボックスの容量が十分にあること</li>
            <li>迷惑メール設定で受信できるようにすること</li>
        </ul>
    </div>

    <div class="box">
        <div class="box-title">うまくアクセスできない場合</div>
        <ul>
            <li>パスワードリセットメールが届かない場合は、迷惑メールフォルダを確認してください</li>
            <li>メールアドレスにアクセスできない場合は、下記のサポート連絡先にお問い合わせください</li>
            <li>2要素認証が有効な場合は、別途対応が必要な場合があります</li>
        </ul>
    </div>

    <h2>サポート連絡先</h2>
    <div class="box">
        <p>アクセスに関してご不明な点がありましたら、以下にお問い合わせください。</p>
        <div class="info-row"><strong>サポートメール:</strong> {{ $supportEmail }}</div>
        <div class="info-row"><strong>アプリURL:</strong> {{ $appUrl }}</div>
        <p style="margin-top: 8px; font-size: 10pt;">
            お問い合わせの際は、この案内状と登録者のメールアドレス（{{ $userEmail }}）をお伝えください。
        </p>
    </div>

    <div class="footer">
        <p>発行日: {{ $generatedDate }}</p>
        <p>{{ $appName }}</p>
        <p>この案内状は大切に保管してください</p>
    </div>
</body>
</html>
