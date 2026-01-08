<?php
/**
 * 綠界 ECPay 金流測試頁面
 * 用於驗證 CheckMacValue 計算是否正確
 * 參考官方文檔: https://developers.ecpay.com.tw/?p=2864
 * 
 * 測試步驟:
 * 1. 訪問此頁面查看計算過程
 * 2. 點擊"送出付款"按鈕
 * 3. 會跳轉到綠界測試環境付款頁面
 * 4. 使用測試信用卡: 4311-9522-2222-2222
 * 5. 測試環境不會真的扣款
 */

// 測試環境參數（來自綠界官方測試帳號）
$MerchantID = '2000132';
$HashKey = '5294y06JbISpM5x9';
$HashIV = 'v77hoKGq4kWxNNIS';

// 訂單資料
$MerchantTradeNo = 'TEST' . time();  // 訂單編號（唯一值）
$MerchantTradeDate = date('Y/m/d H:i:s');  // 訂單時間
$TotalAmount = 100;  // 交易金額（新台幣）
$TradeDesc = '測試訂單';  // 交易描述
$ItemName = '測試商品';  // 商品名稱
$ReturnURL = 'http://your-domain.com/notify.php';  // 付款結果通知網址（需改為你的網址）
$OrderResultURL = 'http://your-domain.com/result.php';  // 付款完成導回網址（需改為你的網址）

// 組合訂單參數
$order_params = [
    'MerchantID'        => $MerchantID,
    'MerchantTradeNo'   => $MerchantTradeNo,
    'MerchantTradeDate' => $MerchantTradeDate,
    'PaymentType'       => 'aio',
    'TotalAmount'       => $TotalAmount,
    'TradeDesc'         => $TradeDesc,
    'ItemName'          => $ItemName,
    'ReturnURL'         => $ReturnURL,
    'ChoosePayment'     => 'ALL',
    'EncryptType'       => 1,
];

// 如果有設定 OrderResultURL 則加入
if (!empty($OrderResultURL)) {
    $order_params['OrderResultURL'] = $OrderResultURL;
}

/**
 * 生成 CheckMacValue（依照綠界官方規範）
 * 步驟:
 * 1. 將參數依照 A-Z 排序
 * 2. 串接成字串: HashKey=xxx&key1=value1&key2=value2&HashIV=xxx
 * 3. URL encode
 * 4. 轉小寫
 * 5. 將特定字符還原: - _ . ! * ( )
 * 6. SHA256 加密
 * 7. 轉大寫
 */
function generateCheckMacValue($params, $HashKey, $HashIV)
{
    // Step 1: 依照 A-Z 排序
    ksort($params);
    
    // Step 2: 組合字串
    $check_str = 'HashKey=' . $HashKey;
    foreach ($params as $key => $value) {
        if ($value !== '' && $value !== null) {
            $check_str .= '&' . $key . '=' . $value;
        }
    }
    $check_str .= '&HashIV=' . $HashIV;
    
    // Step 3: URL encode
    $check_str = urlencode($check_str);
    
    // Step 4: 轉小寫
    $check_str = strtolower($check_str);
    
    // Step 5: 將特定字符還原
    $check_str = str_replace('%2d', '-', $check_str);  // -
    $check_str = str_replace('%5f', '_', $check_str);  // _
    $check_str = str_replace('%2e', '.', $check_str);  // .
    $check_str = str_replace('%21', '!', $check_str);  // !
    $check_str = str_replace('%2a', '*', $check_str);  // *
    $check_str = str_replace('%28', '(', $check_str);  // (
    $check_str = str_replace('%29', ')', $check_str);  // )
    
    // Step 6: SHA256 加密
    $check_mac_value = hash('sha256', $check_str);
    
    // Step 7: 轉大寫
    return strtoupper($check_mac_value);
}

// 計算 CheckMacValue
$CheckMacValue = generateCheckMacValue($order_params, $HashKey, $HashIV);
$order_params['CheckMacValue'] = $CheckMacValue;

// 綠界測試環境網址
$api_url = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>綠界 ECPay 金流測試</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #11998e;
        }
        .param-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        .param-table th,
        .param-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .param-table th {
            background: #667eea;
            color: white;
            font-weight: bold;
            width: 200px;
        }
        .param-table td {
            background: white;
            word-break: break-all;
        }
        .param-table tr:last-child td {
            border-bottom: none;
        }
        .code-block {
            background: #2d3748;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
            margin-top: 15px;
        }
        .highlight {
            background: #ffeaa7;
            padding: 2px 5px;
            border-radius: 3px;
            color: #2d3436;
            font-weight: bold;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info-box strong {
            color: #1976d2;
        }
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .warning-box strong {
            color: #f57c00;
        }
        .btn-submit {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            display: block;
            margin: 30px auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .step {
            counter-increment: step-counter;
            margin-bottom: 15px;
            padding-left: 35px;
            position: relative;
        }
        .step::before {
            content: counter(step-counter);
            background: #667eea;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 25px;
            font-weight: bold;
            position: absolute;
            left: 0;
            top: 0;
        }
        .steps-container {
            counter-reset: step-counter;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏦 綠界 ECPay 金流測試</h1>
            <p>CheckMacValue 計算驗證工具</p>
        </div>

        <div class="content">
            <!-- 訂單參數 -->
            <div class="section">
                <h2>📋 訂單參數</h2>
                <table class="param-table">
                    <?php foreach ($order_params as $key => $value): ?>
                        <?php if ($key !== 'CheckMacValue'): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($key); ?></th>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- CheckMacValue 計算過程 -->
            <div class="section">
                <h2>🔐 CheckMacValue 計算過程</h2>
                
                <div class="info-box">
                    <strong>計算結果：</strong> <span class="highlight"><?php echo $CheckMacValue; ?></span>
                </div>

                <div class="steps-container">
                    <?php
                    // 重新計算以顯示過程
                    $sorted_params = $order_params;
                    unset($sorted_params['CheckMacValue']);
                    ksort($sorted_params);
                    
                    $step1_str = 'HashKey=' . $HashKey;
                    foreach ($sorted_params as $key => $value) {
                        if ($value !== '' && $value !== null) {
                            $step1_str .= '&' . $key . '=' . $value;
                        }
                    }
                    $step1_str .= '&HashIV=' . $HashIV;
                    
                    $step2_str = urlencode($step1_str);
                    $step3_str = strtolower($step2_str);
                    $step4_str = str_replace(['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'], 
                                            ['-', '_', '.', '!', '*', '(', ')'], $step3_str);
                    $step5_str = hash('sha256', $step4_str);
                    $step6_str = strtoupper($step5_str);
                    ?>
                    
                    <div class="step">
                        <strong>參數依 A-Z 排序並串接：</strong>
                        <div class="code-block"><?php echo htmlspecialchars($step1_str); ?></div>
                    </div>
                    
                    <div class="step">
                        <strong>URL Encode：</strong>
                        <div class="code-block"><?php echo htmlspecialchars(mb_substr($step2_str, 0, 200)) . '...'; ?></div>
                    </div>
                    
                    <div class="step">
                        <strong>轉小寫：</strong>
                        <div class="code-block"><?php echo htmlspecialchars(mb_substr($step3_str, 0, 200)) . '...'; ?></div>
                    </div>
                    
                    <div class="step">
                        <strong>還原特殊字符 (- _ . ! * ( ))：</strong>
                        <div class="code-block"><?php echo htmlspecialchars(mb_substr($step4_str, 0, 200)) . '...'; ?></div>
                    </div>
                    
                    <div class="step">
                        <strong>SHA256 加密：</strong>
                        <div class="code-block"><?php echo $step5_str; ?></div>
                    </div>
                    
                    <div class="step">
                        <strong>轉大寫（最終結果）：</strong>
                        <div class="code-block" style="background: #27ae60; color: white;"><?php echo $step6_str; ?></div>
                    </div>
                </div>
            </div>

            <!-- 測試說明 -->
            <div class="section">
                <h2>📝 測試說明</h2>
                <div class="warning-box">
                    <strong>⚠️ 注意事項：</strong>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>這是綠界官方的測試環境，不會真的扣款</li>
                        <li>測試信用卡號：<strong>4311-9522-2222-2222</strong></li>
                        <li>安全碼：任意3碼數字</li>
                        <li>有效期限：任意未來日期</li>
                        <li>ReturnURL 和 OrderResultURL 需要改成你的網址才能接收付款通知</li>
                    </ul>
                </div>
            </div>

            <!-- 提交表單 -->
            <form id="ecpay_form" method="POST" action="<?php echo $api_url; ?>">
                <?php foreach ($order_params as $key => $value): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endforeach; ?>
                <button type="submit" class="btn-submit">🚀 送出付款測試</button>
            </form>

            <div class="info-box" style="text-align: center;">
                <p>點擊按鈕後將跳轉到綠界測試環境付款頁面</p>
                <p style="margin-top: 5px; font-size: 12px; opacity: 0.7;">API URL: <?php echo $api_url; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
