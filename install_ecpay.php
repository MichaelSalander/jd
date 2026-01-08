<?php
/**
 * 綠界支付插件資料庫安裝腳本
 * 直接在資料庫中安裝綠界支付插件
 * 使用方法：訪問 http://localhost/shopxo/install_ecpay.php
 */

// 資料庫配置（從 config/database.php 讀取）
require_once __DIR__ . '/config/database.php';

// 連接資料庫
$config = include __DIR__ . '/config/database.php';
$db_config = $config['connections']['mysql'];

$conn = new mysqli(
    $db_config['hostname'],
    $db_config['username'],
    $db_config['password'],
    $db_config['database']
);

if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// 表前綴
$prefix = $db_config['prefix'];

// 檢查是否已安裝
$check_sql = "SELECT id FROM {$prefix}plugins_payment WHERE payment = 'Ecpay'";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    echo "<h2>✅ 綠界支付已經安裝</h2>";
    echo "<p>請直接到後台編輯配置參數</p>";
    echo "<p><a href='/shopxo/adminaskdmg.php/plugins/index'>前往支付管理</a></p>";
} else {
    // 測試環境參數（預設填入）
    $config_data = json_encode([
        'merchant_id' => '2000132',
        'hash_key' => '5294y06JbISpM5x9',
        'hash_iv' => 'v77hoKGq4kWxNNIS',
        'environment' => 'test',
        'payment_type' => 'ALL'
    ], JSON_UNESCAPED_UNICODE);

    // 插入綠界支付配置
    $insert_sql = "INSERT INTO {$prefix}plugins_payment 
        (name, payment, logo, apply_terminal, config, element, is_enable, is_open_user, sort, add_time, upd_time) 
        VALUES 
        ('綠界金流（ECPay）', 'Ecpay', '', 'pc,h5,ios,android', ?, '', 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
    
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param('s', $config_data);
    
    if ($stmt->execute()) {
        echo "<h2 style='color: green;'>✅ 綠界支付安裝成功！</h2>";
        echo "<h3>📋 已使用測試環境參數：</h3>";
        echo "<ul>";
        echo "<li><strong>商店代號：</strong>2000132</li>";
        echo "<li><strong>HashKey：</strong>5294y06JbISpM5x9</li>";
        echo "<li><strong>HashIV：</strong>v77hoKGq4kWxNNIS</li>";
        echo "<li><strong>環境：</strong>測試環境</li>";
        echo "<li><strong>支付方式：</strong>ALL（讓用戶選擇）</li>";
        echo "<li><strong>啟用狀態：</strong>✅ 已啟用</li>";
        echo "<li><strong>對用戶開放：</strong>✅ 是</li>";
        echo "</ul>";
        echo "<h3>🎯 下一步：</h3>";
        echo "<ol>";
        echo "<li>前台創建訂單，應該會看到綠界支付選項</li>";
        echo "<li>使用測試卡號：<strong>4311-9522-2222-2222</strong></li>";
        echo "<li>如需修改參數，到後台支付管理編輯</li>";
        echo "<li>正式上線前，記得更換成正式環境參數</li>";
        echo "</ol>";
        echo "<p><a href='/shopxo/adminaskdmg.php/plugins/index' style='background: #11998e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>前往支付管理</a></p>";
        echo "<p><a href='/shopxo/' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>前往前台測試</a></p>";
    } else {
        echo "<h2 style='color: red;'>❌ 安裝失敗</h2>";
        echo "<p>錯誤信息：" . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

$conn->close();

echo "<hr>";
echo "<p style='color: #666; font-size: 12px;'>⚠️ 安裝完成後，請刪除此文件：install_ecpay.php</p>";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>綠界支付安裝</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 { color: #333; }
        ul { line-height: 2; }
        ol { line-height: 2; }
        strong { color: #11998e; }
    </style>
</head>
<body>
</body>
</html>
