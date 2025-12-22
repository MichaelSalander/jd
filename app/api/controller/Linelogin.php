<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\api\controller;

use app\service\ApiService;
use app\service\UserService;
use app\service\SystemBaseService;

/**
 * LINE 登入
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2025-12-23T00:00:00+0800
 */
class Linelogin extends Common
{
    /**
     * LINE 授權登入入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2025-12-23T00:00:00+0800
     */
    public function Authorize()
    {
        // LINE Channel 配置
        $channelId = '2002328176';
        $callbackUrl = 'https://orthopterous-spleenfully-zander.ngrok-free.dev/shopxo/public/api.php/linelogin/callback';
        
        // 調試輸出
        if (empty($channelId)) {
            die('Error: Channel ID is empty');
        }
        if (empty($callbackUrl)) {
            die('Error: Callback URL is empty');
        }
        
        // LINE OAuth 2.0 授權 URL
        $state = md5(uniqid(rand(), true));
        
        // 將 state 存入 session 用於驗證
        session('line_oauth_state', $state);
        
        // 構建授權 URL
        $params = [
            'response_type' => 'code',
            'client_id' => $channelId,
            'redirect_uri' => $callbackUrl,
            'state' => $state,
            'scope' => 'profile openid email',
        ];
        
        // 調試輸出完整 URL
        $authorizeUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);
        
        // 使用 header 重定向
        header('Location: ' . $authorizeUrl);
        exit;
    }
    
    /**
     * LINE 授權回調處理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2025-12-23T00:00:00+0800
     */
    public function Callback()
    {
        // 獲取授權碼
        $code = input('code', '');
        $state = input('state', '');
        $error = input('error', '');
        
        // 檢查是否授權失敗
        if (!empty($error)) {
            return ApiService::ApiDataReturn(MyLang('common_service.user.login_third_openid_no_exist_tips'), -1);
        }
        
        // 驗證 state 參數防止 CSRF 攻擊
        $sessionState = session('line_oauth_state');
        if (empty($state) || $state !== $sessionState) {
            return ApiService::ApiDataReturn('State 驗證失敗，請重新授權', -1);
        }
        
        // 清除 session 中的 state
        session('line_oauth_state', null);
        
        // 如果沒有授權碼
        if (empty($code)) {
            return ApiService::ApiDataReturn('授權碼獲取失敗', -1);
        }
        
        // 使用授權碼換取 Access Token
        $tokenData = $this->GetAccessToken($code);
        if (empty($tokenData['access_token'])) {
            return ApiService::ApiDataReturn('Access Token 獲取失敗', -1);
        }
        
        // 獲取用戶資料
        $userInfo = $this->GetUserProfile($tokenData['access_token']);
        if (empty($userInfo['userId'])) {
            return ApiService::ApiDataReturn('用戶資料獲取失敗', -1);
        }
        
        // 解析 ID Token 獲取 email（如果有）
        $email = '';
        if (!empty($tokenData['id_token'])) {
            $idTokenData = $this->ParseIdToken($tokenData['id_token']);
            $email = $idTokenData['email'] ?? '';
        }
        
        // 處理用戶登入邏輯
        $params = [
            'openid' => $userInfo['userId'],
            'nickname' => $userInfo['displayName'] ?? 'LINE用戶',
            'avatar' => $userInfo['pictureUrl'] ?? '',
            'province' => '',
            'city' => '',
            'gender' => 0, // LINE 不提供性別資訊
            'line_unionid' => $userInfo['userId'], // LINE 沒有 unionid 概念，使用 userId
            'line_email' => $email,
        ];
        
        // 調用統一的第三方登入處理
        $ret = UserService::AuthUserProgram($params, 'line_openid');
        
        // 登入成功後重定向到首頁或指定頁面
        if ($ret['code'] == 0) {
            // 設置用戶 session
            if (!empty($ret['data'])) {
                session('user', $ret['data']);
            }
            
            // 重定向到首頁
            $redirectUrl = 'https://orthopterous-spleenfully-zander.ngrok-free.dev/shopxo/public/';
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            return ApiService::ApiDataReturn($ret['msg'], -1);
        }
    }
    
    /**
     * 使用授權碼換取 Access Token
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2025-12-23T00:00:00+0800
     * @param    string $code 授權碼
     * @return   array
     */
    private function GetAccessToken($code)
    {
        $channelId = '2002328176';
        $channelSecret = '93690ede2ea39d645e775c8682b9c2e3';
        $callbackUrl = 'https://orthopterous-spleenfully-zander.ngrok-free.dev/shopxo/public/api.php/linelogin/callback';
        
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $callbackUrl,
            'client_id' => $channelId,
            'client_secret' => $channelSecret,
        ];
        
        $url = 'https://api.line.me/oauth2/v2.1/token';
        
        // 使用 curl 發送 POST 請求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && !empty($response)) {
            return json_decode($response, true);
        }
        
        return [];
    }
    
    /**
     * 獲取用戶資料
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2025-12-23T00:00:00+0800
     * @param    string $accessToken Access Token
     * @return   array
     */
    private function GetUserProfile($accessToken)
    {
        $url = 'https://api.line.me/v2/profile';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && !empty($response)) {
            return json_decode($response, true);
        }
        
        return [];
    }
    
    /**
     * 解析 ID Token 獲取 email
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2025-12-23T00:00:00+0800
     * @param    string $idToken ID Token
     * @return   array
     */
    private function ParseIdToken($idToken)
    {
        // ID Token 是 JWT 格式，分為三部分：header.payload.signature
        $parts = explode('.', $idToken);
        
        if (count($parts) !== 3) {
            return [];
        }
        
        // 解碼 payload 部分
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        $data = json_decode($payload, true);
        
        return $data ?? [];
    }
}
