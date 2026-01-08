<?php
/**
 * 批量下架所有商品
 */

// 數據庫配置
$config = require __DIR__ . '/config/database.php';
$db_config = $config['connections'][$config['default']];

// 連接數據庫
$mysqli = new mysqli(
    $db_config['hostname'],
    $db_config['username'],
    $db_config['password'],
    $db_config['database'],
    $db_config['hostport']
);

// 檢查連接
if ($mysqli->connect_error) {
    die("數據庫連接失敗: " . $mysqli->connect_error);
}

// 設置字符集
$mysqli->set_charset($db_config['charset']);

try {
    // 獲取表前綴
    $prefix = $db_config['prefix'];
    
    // 獲取所有上架的商品數量
    $count_sql = "SELECT COUNT(*) as count FROM {$prefix}goods WHERE is_shelves = 1";
    $result = $mysqli->query($count_sql);
    $row = $result->fetch_assoc();
    $shelved_count = $row['count'];
    
    if ($shelved_count == 0) {
        echo "✓ 沒有需要下架的商品\n";
        exit;
    }
    
    echo "找到 {$shelved_count} 個上架的商品\n";
    echo "開始批量下架...\n\n";
    
    // 批量下架所有商品
    $update_sql = "UPDATE {$prefix}goods SET is_shelves = 0, upd_time = " . time() . " WHERE is_shelves = 1";
    
    if ($mysqli->query($update_sql)) {
        $affected = $mysqli->affected_rows;
        echo "✓ 成功下架 {$affected} 個商品\n";
        echo "\n【操作完成】\n";
        echo "- 前端商品將不再顯示\n";
        echo "- 如需重新上架，請到後台「商品管理」手動操作\n";
    } else {
        echo "✗ 下架失敗: " . $mysqli->error . "\n";
    }
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
} finally {
    $mysqli->close();
}
