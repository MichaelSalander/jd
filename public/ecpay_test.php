<?php
/**
 * 綠界支付 CheckMacValue 測試頁面
 * 用於驗證 CheckMacValue 生成是否正確
 */

// 測試環境參數（綠界官方測試帳號）
$config = [
    'merchant_id' => '2000132',
    'hash_key'    => '5294y06JbISpM5x9',
    'hash_iv'     => 'v77hoKGq4kWxNNIS',
];

// 測試訂單參數
$test_order = [
    'MerchantID'        => $config['merchant_id'],
    'MerchantTradeNo'   => 'TEST' . time(),
    'MerchantTradeDate' => date('Y/m/d H:i:s'),
    'PaymentType'       => 'aio',
    'TotalAmount'       => 1000,
    'TradeDesc'         => urlencode('測試訂單'),
    'ItemName'          => '測試商品',
    'ReturnURL'         => 'http://localhost/shopxo/public/ecpay_test.php',
    'ChoosePayment'     => 'ALL',
    'EncryptType'       => 1,
    'NeedExtraPaidInfo' => 'N',
];

/**
 * 生成 CheckMacValue
 */
function generateCheckMacValue($params, $config)
{
    // 移除 CheckMacValue
    unset($params['CheckMacValue']);

    // 按照鍵值 A-Z 排序
    ksort($params);

    // 組合字串
    $check_str = 'HashKey=' . $config['hash_key'];
    foreach($params as $key => $value)
    {
        if($value !== '' && $value !== null)
        {
            $check_str .= '&' . $key . '=' . $value;
        }
    }
    $check_str .= '&HashIV=' . $config['hash_iv'];

    // 進行 URL encode
    $check_str = urlencode($check_str);

    // 轉小寫
    $check_str = strtolower($check_str);

    // 還原特定字符
    $check_str = str_replace('%2d', '-', $check_str);
    $check_str = str_replace('%5f', '_', $check_str);
    $check_str = str_replace('%2e', '.', $check_str);
    $check_str = str_replace('%21', '!', $check_str);
    $check_str = str_replace('%2a', '*', $check_str);
    $check_str = str_replace('%28', '(', $check_str);
    $check_str = str_replace('%29', ')', $check_str);

    // SHA256 加密並轉大寫
    $check_mac_value = hash('sha256', $check_str);

    return [
        'check_str'       => $check_str,
        'check_mac_value' => strtoupper($check_mac_value),
    ];
}

// 生成 CheckMacValue
$result = generateCheckMacValue($test_order, $config);
$test_order['CheckMacValue'] = $result['check_mac_value'];

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>綠界支付測試頁面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #00a65a;
            border-bottom: 3px solid #00a65a;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
        }
        .info-box {
            background: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #00a65a;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
            border-radius: 4px;
        }
        .param-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .param-table th,
        .param-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .param-table th {
            background-color: #00a65a;
            color: white;
        }
        .param-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn-primary {
            background: #00a65a;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px 0;
        }
        .btn-primary:hover {
            background: #008d4c;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: Consolas, monospace;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 綠界金流支付測試</h1>

        <div class="info-box">
            <strong>📝 測試說明：</strong><br>
            這是綠界支付的測試頁面。點擊下方按鈕會跳轉到綠界測試環境進行支付測試。
        </div>

        <div class="warning-box">
            <strong>⚠️ 注意事項：</strong><br>
            • 這是<strong>測試環境</strong>，不會產生實際交易<br>
            • 測試信用卡號：<code>4311-9522-2222-2222</code><br>
            • 有效期：任意未來日期（例如：12/25）<br>
            • CVV：任意3碼（例如：123）
        </div>

        <h2>📋 測試配置資訊</h2>
        <table class="param-table">
            <tr>
                <th>參數名稱</th>
                <th>參數值</th>
            </tr>
            <tr>
                <td>商店代號（Merchant ID）</td>
                <td><?= $config['merchant_id'] ?></td>
            </tr>
            <tr>
                <td>HashKey</td>
                <td><?= $config['hash_key'] ?></td>
            </tr>
            <tr>
                <td>HashIV</td>
                <td><?= $config['hash_iv'] ?></td>
            </tr>
        </table>

        <h2>📦 訂單參數</h2>
        <table class="param-table">
            <tr>
                <th>參數名稱</th>
                <th>參數值</th>
            </tr>
            <?php foreach($test_order as $key => $value): ?>
            <tr>
                <td><?= $key ?></td>
                <td><?= $key === 'CheckMacValue' ? '<strong style="color: #00a65a;">' . $value . '</strong>' : htmlspecialchars($value) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>🔐 CheckMacValue 計算過程</h2>
        <div class="info-box">
            <strong>加密前字串：</strong><br>
            <pre><?= htmlspecialchars($result['check_str']) ?></pre>
        </div>
        <div class="info-box">
            <strong>SHA256 加密後：</strong><br>
            <code style="font-size: 14px;"><?= $result['check_mac_value'] ?></code>
        </div>

        <h2>🚀 開始測試</h2>
        <form id="ecpayForm" method="POST" action="https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5">
            <?php foreach($test_order as $key => $value): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn-primary">💳 前往綠界測試環境支付</button>
        </form>

        <div class="warning-box" style="margin-top: 30px;">
            <strong>🔍 測試步驟：</strong><br>
            1. 點擊上方「前往綠界測試環境支付」按鈕<br>
            2. 選擇「信用卡」支付方式<br>
            3. 輸入測試信用卡號：<code>4311-9522-2222-2222</code><br>
            4. 有效期輸入未來日期，CVV輸入3位數字<br>
            5. 完成支付後會返回商店<br>
            6. 如果成功跳轉到綠界頁面，表示 <strong>CheckMacValue 計算正確</strong> ✅
        </div>

        <div class="info-box" style="margin-top: 20px;">
            <strong>📌 常見問題：</strong><br>
            <strong>Q: 如果出現「檢查碼錯誤」怎麼辦？</strong><br>
            A: 表示 CheckMacValue 計算有誤，請檢查 HashKey 和 HashIV 是否正確。<br><br>
            
            <strong>Q: 如果頁面一直轉圈或白屏？</strong><br>
            A: 可能是網路問題或綠界測試環境維護，請稍後再試。<br><br>
            
            <strong>Q: 測試成功後如何在 ShopXO 使用？</strong><br>
            A: 到後台「網站 → 支付方式」安裝綠界支付插件，填入相同的測試帳號資訊即可。
        </div>
    </div>
</body>
</html>
