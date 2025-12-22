<?php
// +----------------------------------------------------------------------
// | ShopXO 國內領先企業級B2C免費開源電商系統
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\service\UserService;

/**
 * LINE 登入授權處理（Web 應用端）
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  1.0.0
 * @datetime 2025-12-23T00:00:00+0800
 */
class Lineauth extends Common
{
    /**
     * 處理 LINE 登入回調（從 API 應用轉發過來）
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2025-12-23T00:00:00+0800
     */
    public function Index()
    {
        // 從 URL 獲取 token
        $token = input('token', '');
        
        if (empty($token)) {
            $this->assign('msg', 'Token 參數缺失');
            return $this->fetch('public/tips_error');
        }
        
        // 通過 token 獲取用戶信息
        $user = UserService::UserTokenData($token);
        
        if (empty($user) || empty($user['id'])) {
            $this->assign('msg', 'Token 無效或已過期，請重新登入');
            return $this->fetch('public/tips_error');
        }
        
        // 確保 token 存在於用戶數據中
        if (empty($user['token'])) {
            $user['token'] = $token;
        }
        
        // 記錄登入（這會在 web 應用中設置 session 和 user_info cookie）
        if (UserService::UserLoginRecord(0, $user)) {
            // 額外設置 user_token_data cookie，讓系統能持久讀取 token
            MyCookie('user_token_data', $user['token'], true);
            
            // 登入成功，重定向到首頁
            return redirect(__MY_URL__);
        } else {
            $this->assign('msg', '登入處理失敗，請重試');
            return $this->fetch('public/tips_error');
        }
    }
}
