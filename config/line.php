<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | LINE Login 配置
// +----------------------------------------------------------------------

return [
    // LINE Channel ID
    'channel_id' => '2002328176',
    
    // LINE Channel Secret
    'channel_secret' => '93690ede2ea39d645e775c8682b9c2e3',
    
    // 開發環境 ngrok URL（每次啟動 ngrok 後需要更新此處）
    // 設置為空則自動使用當前域名
    'ngrok_url' => 'https://orthopterous-spleenfully-zander.ngrok-free.dev',
    
    // 回調路徑（相對路徑）
    'callback_path' => '/shopxo/public/api.php/linelogin/callback',
    
    // 登入成功後重定向路徑
    'success_redirect_path' => '/shopxo/public/index.php',
];
