<?php
/**
 * 批量下架所有商品
 */
// 引入ThinkPHP框架
namespace think;
require __DIR__ . '/vendor/autoload.php';

// 引入配置
$rootPath = __DIR__ . '/';
define('ROOT_PATH', $rootPath);

// 數據庫配置
$database = require __DIR__ . '/config/database.php';

use think\facade\Db as DbFacade;

use think\facade\Db as DbFacade;

// 配置數據庫連接
$config = new \think\Config();
$config->set($database, 'database');
DbFacade::setConfig($config);

try {
    // 獲取所有上架的商品數量
    $shelved_count = DbFacade::name('Goods')->where(['is_shelves' => 1])->count();
    
    if ($shelved_count == 0) {
        echo "沒有需要下架的商品\n";
        exit;
    }
    
    echo "找到 {$shelved_count} 個上架的商品\n";
    echo "開始批量下架...\n\n";
    
    // 批量下架所有商品
    $result = DbFacade::name('Goods')->where(['is_shelves' => 1])->update([
        'is_shelves' => 0,
        'upd_time' => time()
    ]);
    
    if ($result) {
        echo "✓ 成功下架 {$result} 個商品\n";
        echo "\n【操作完成】\n";
        echo "- 如需重新上架，請到後台手動操作\n";
        echo "- 或者使用SQL: UPDATE sxo_goods SET is_shelves=1, upd_time=" . time() . "\n";
    } else {
        echo "✗ 下架失敗或沒有商品需要更新\n";
    }
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
}
