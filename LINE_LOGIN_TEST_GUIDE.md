# LINE Login 測試指南

## 前置準備

### 1. 執行數據庫遷移
```sql
-- 在 MySQL 中執行以下 SQL
source c:\xampp\htdocs\shopxo\runtime\data\migration_add_line_fields.sql
```

或手動執行：
```sql
ALTER TABLE `sxo_user_platform` 
ADD COLUMN `line_openid` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'LINE openid' AFTER `kuaishou_openid`,
ADD COLUMN `line_unionid` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'LINE unionid' AFTER `line_openid`,
ADD COLUMN `line_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'LINE email' AFTER `line_unionid`,
ADD INDEX `line_openid`(`line_openid` ASC) USING BTREE,
ADD INDEX `line_unionid`(`line_unionid` ASC) USING BTREE,
ADD INDEX `line_email`(`line_email` ASC) USING BTREE;
```

### 2. 啟動 ngrok
```bash
ngrok http 80
```

確保獲得的 URL 為：`https://orthopterous-spleenfully-zander.ngrok-free.dev`

### 3. 更新 LINE Developer Console
登入 LINE Developers Console，確認以下設置：
- **Channel ID**: 2002328176
- **Channel Secret**: 93690ede2ea39d645e775c8682b9c2e3
- **Callback URL**: `https://orthopterous-spleenfully-zander.ngrok-free.dev/shopxo/public/index.php/api/linelogin/callback`

## 測試流程

### 步驟 1: 訪問登入頁面
```
https://orthopterous-spleenfully-zander.ngrok-free.dev/shopxo/public/index.php/index/user/logininfo
```

### 步驟 2: 點擊 LINE 登入按鈕
- 頁面底部應顯示綠色的「LINE 登入」按鈕
- 點擊後會重定向到 LINE 授權頁面

### 步驟 3: LINE 授權
- 在 LINE 授權頁面登入您的 LINE 帳號
- 同意授權應用訪問您的個人資料

### 步驟 4: 驗證回調
- 授權成功後會重定向回 ShopXO
- 系統會自動創建或綁定用戶帳號
- 登入成功後重定向到首頁

## 數據驗證

### 檢查用戶平台表
```sql
SELECT 
    id, user_id, line_openid, line_unionid, line_email, add_time
FROM 
    sxo_user_platform
WHERE 
    line_openid != ''
ORDER BY 
    add_time DESC;
```

### 檢查用戶表
```sql
SELECT 
    u.id, u.username, u.nickname, u.mobile, u.avatar, u.add_time,
    up.line_openid, up.line_email
FROM 
    sxo_user u
LEFT JOIN 
    sxo_user_platform up ON u.id = up.user_id
WHERE 
    up.line_openid != ''
ORDER BY 
    u.add_time DESC;
```

## API 端點

### 授權入口
```
GET /index.php/api/linelogin/authorize
```
功能：重定向到 LINE OAuth 授權頁面

### 授權回調
```
GET /index.php/api/linelogin/callback?code={code}&state={state}
```
功能：處理 LINE 授權回調，完成用戶登入

## 預期結果

### 成功場景
1. **新用戶首次登入**
   - 自動創建新用戶帳號
   - `sxo_user` 表新增記錄
   - `sxo_user_platform` 表新增記錄，包含 LINE 資訊
   - 用戶暱稱使用 LINE displayName
   - 頭像使用 LINE pictureUrl

2. **已有帳號用戶**
   - 根據 line_openid 查找現有帳號
   - 自動登入該帳號

### 錯誤處理
1. **授權失敗**：顯示錯誤訊息「授權失敗」
2. **State 驗證失敗**：顯示「State 驗證失敗，請重新授權」
3. **Token 獲取失敗**：顯示「Access Token 獲取失敗」
4. **用戶資料獲取失敗**：顯示「用戶資料獲取失敗」

## 故障排除

### 問題 1: 點擊按鈕無反應
**解決方案**：
- 清除瀏覽器緩存
- 檢查 ngrok 是否正常運行
- 查看瀏覽器控制台是否有 JavaScript 錯誤

### 問題 2: LINE 授權頁面顯示錯誤
**解決方案**：
- 確認 LINE Channel ID 正確
- 確認 Callback URL 與 LINE Console 設置一致
- 檢查 ngrok URL 是否改變

### 問題 3: 回調後顯示錯誤
**解決方案**：
- 檢查數據庫遷移是否執行成功
- 查看 `runtime/log/` 目錄下的日誌文件
- 確認 Channel Secret 設置正確

### 問題 4: 無法獲取 Email
**說明**：
- LINE 的 email 需要用戶在授權時明確同意
- 用戶可能未在 LINE 設置中綁定 email
- 這是正常現象，系統會優雅處理

## 開發環境配置

### LINE Channel 配置位置
文件：`app/api/controller/Linelogin.php`

```php
// 第 31 行
$channelId = MyConfig('third_party_line_client_id', '2002328176');

// 第 32 行
$callbackUrl = MyConfig('third_party_line_callback_url', 
    SystemBaseService::AttachmentHost() . '/index.php/api/linelogin/callback');

// 第 149 行
$channelSecret = MyConfig('third_party_line_client_secret', 
    '93690ede2ea39d645e775c8682b9c2e3');
```

**建議**：生產環境應將這些配置移至數據庫配置表或 `.env` 文件

## 生產部署清單

- [ ] 更新 Callback URL 為正式域名
- [ ] 將 Channel Secret 移至環境變量或加密存儲
- [ ] 配置 HTTPS 證書
- [ ] 在 LINE Console 添加正式域名到白名單
- [ ] 測試完整登入流程
- [ ] 監控錯誤日誌
- [ ] 設置用戶數據備份

## 後續優化建議

1. **配置管理**：將 LINE 憑證移至後台配置頁面
2. **日誌記錄**：添加詳細的 OAuth 流程日誌
3. **錯誤處理**：完善錯誤提示，支持多語言
4. **用戶體驗**：添加登入狀態動畫效果
5. **安全加固**：實現 nonce 參數防重放攻擊
6. **帳號綁定**：允許已登入用戶綁定 LINE 帳號
7. **多平台支持**：支持 LINE 在不同平台的授權
