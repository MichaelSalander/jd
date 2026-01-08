<?php
/**
 * 綠界支付配置更新腳本
 * 直接更新資料庫中的配置，繞過後台驗證
 * 使用方法：訪問 http://localhost/shopxo/update_ecpay_config.php
 */

// 資料庫配置
require_once __DIR__ . '/config/database.php';

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
$check_sql = "SELECT id, name, config, is_enable, is_open_user FROM {$prefix}plugins_payment WHERE payment = 'Ecpay'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    echo "<h2 style='color: red;'>❌ 綠界支付尚未安裝</h2>";
    echo "<p>請先在後台安裝綠界支付插件</p>";
} else {
    $row = $result->fetch_assoc();
    
    echo "<h2>📋 當前配置</h2>";
    echo "<ul>";
    echo "<li><strong>插件ID：</strong>" . $row['id'] . "</li>";
    echo "<li><strong>插件名稱：</strong>" . $row['name'] . "</li>";
    echo "<li><strong>啟用狀態：</strong>" . ($row['is_enable'] == 1 ? '✅ 已啟用' : '❌ 未啟用') . "</li>";
    echo "<li><strong>對用戶開放：</strong>" . ($row['is_open_user'] == 1 ? '✅ 是' : '❌ 否') . "</li>";
    echo "</ul>";
    
    // 測試環境參數
    $config_data = json_encode([
        'merchant_id' => '2000132',
        'hash_key' => '5294y06JbISpM5x9',
        'hash_iv' => 'v77hoKGq4kWxNNIS',
        'environment' => 'test',
        'payment_type' => 'ALL'
    ], JSON_UNESCAPED_UNICODE);
    
    // apply_terminal 需要是 JSON 格式
    $apply_terminal = json_encode(['pc', 'h5', 'ios', 'android']);
    
    // 更新配置
    $update_sql = "UPDATE {$prefix}plugins_payment 
                   SET config = ?, 
                       is_enable = 1, 
                       is_open_user = 1,
                       apply_terminal = ?,
                       upd_time = UNIX_TIMESTAMP() 
                   WHERE payment = 'Ecpay'";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('ss', $config_data, $apply_terminal);
    
    if ($stmt->execute()) {
        echo "<h2 style='color: green;'>✅ 配置更新成功！</h2>";
        echo "<h3>🔑 已設定測試環境參數：</h3>";
        echo "<ul>";
        echo "<li><strong>商店代號：</strong>2000132</li>";
        echo "<li><strong>HashKey：</strong>5294y06JbISpM5x9</li>";
        echo "<li><strong>HashIV：</strong>v77hoKGq4kWxNNIS</li>";
        echo "<li><strong>環境：</strong>測試環境</li>";
        echo "<li><strong>支付方式：</strong>ALL（讓用戶選擇）</li>";
        echo "<li><strong>啟用狀態：</strong>✅ 已啟用</li>";
        echo "<li><strong>對用戶開放：</strong>✅ 是</li>";
        echo "<li><strong>適用終端：</strong>PC、H5、iOS、Android</li>";
        echo "</ul>";
        
        echo "<h3>🎯 現在可以：</h3>";
        echo "<ol>";
        echo "<li><strong>前台創建訂單</strong>，應該會看到綠界支付選項了</li>";
        echo "<li>選擇綠界支付，使用測試卡號：<strong style='color: #e74c3c;'>4311-9522-2222-2222</strong></li>";
        echo "<li>安全碼：任意3碼，有效期限：任意未來日期</li>";
        echo "<li>測試環境不會真的扣款</li>";
        echo "</ol>";
        
        echo "<h3>⚙️ 如需更換正式環境：</h3>";
        echo "<p>1. 到綠界商店後台取得正式的 MerchantID、HashKey、HashIV</p>";
        echo "<p>2. 在後台編輯綠界支付配置即可（應該不會再報錯了）</p>";
        
        echo "<div style='margin-top: 30px;'>";
        echo "<a href='/shopxo/' style='background: #11998e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>前往前台測試</a>";
        echo "<a href='/shopxo/adminaskdmg.php/plugins/index' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>前往後台管理</a>";
        echo "</div>";
        
    } else {
        echo "<h2 style='color: red;'>❌ 更新失敗</h2>";
        echo "<p>錯誤信息：" . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

$conn->close();

echo "<hr style='margin-top: 50px;'>";
echo "<p style='color: #666; font-size: 12px;'>⚠️ 配置完成後，請刪除此文件：update_ecpay_config.php</p>";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>綠界支付配置更新</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        body > * {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h2 { 
            color: #333; 
            border-bottom: 3px solid #11998e;
            padding-bottom: 10px;
        }
        h3 { 
            color: #11998e; 
            margin-top: 30px;
        }
        ul { 
            line-height: 2;
            background: #f8f9fa;
            padding: 20px 40px;
            border-radius: 8px;
        }
        ol { 
            line-height: 2;
            background: #e3f2fd;
            padding: 20px 40px;
            border-radius: 8px;
        }
        strong { color: #11998e; }
        a {
            transition: all 0.3s;
        }
        a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
</body>
</html>
