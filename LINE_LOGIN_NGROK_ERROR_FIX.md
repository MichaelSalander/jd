# LINE Login NGROK 錯誤解決方案

## 🔴 錯誤原因
**NGROK_3200 錯誤**表示 ngrok 隧道已離線或過期。

ngrok 免費版特點：
- 每次重啟會產生新的隨機 URL
- 隧道在一段時間後會自動過期
- 需要保持 ngrok 進程運行

---

## ✅ 快速解決步驟

### 步驟 1: 重新啟動 ngrok

打開 PowerShell 或命令提示字元，執行：

```powershell
ngrok http 80
```

### 步驟 2: 獲取新的 ngrok URL

ngrok 啟動後，會顯示類似以下訊息：

```
Forwarding  https://abc-xyz-123.ngrok-free.app -> http://localhost:80
```

**複製** `https://` 開頭的完整 URL

### 步驟 3: 更新配置文件

打開文件：`c:\xampp\htdocs\shopxo\config\line.php`

修改 `ngrok_url` 的值：

```php
return [
    // ...其他配置...
    
    // 更新為新的 ngrok URL（去掉結尾的斜線）
    'ngrok_url' => 'https://你的新ngrok地址.ngrok-free.app',
    
    // ...其他配置...
];
```

### 步驟 4: 更新 LINE Developer Console

1. 登入 [LINE Developers Console](https://developers.line.biz/console/)
2. 選擇你的 Channel (ID: 2002328176)
3. 進入 **LINE Login** 設定
4. 更新 **Callback URL** 為：
   ```
   https://你的新ngrok地址.ngrok-free.app/shopxo/public/api.php/linelogin/callback
   ```
5. 保存設定

### 步驟 5: 測試 LINE 登入

訪問：
```
https://你的新ngrok地址.ngrok-free.app/shopxo/public/index.php/index/user/logininfo
```

點擊 LINE 登入按鈕進行測試。

---

## 🎯 優化方案（避免每次修改）

### 方案 A: 使用 ngrok 固定域名（付費版）

購買 ngrok 付費方案，可獲得固定的域名，不需要每次修改。

### 方案 B: 生產環境使用真實域名

在正式環境部署時，使用真實的域名和 SSL 證書，不需要 ngrok。

### 方案 C: 本地開發自動檢測

如果在本地開發且不使用 ngrok，可以將配置文件中的 `ngrok_url` 設為空：

```php
'ngrok_url' => '',
```

這樣系統會自動檢測當前訪問的域名（如 `http://localhost`）。

**注意**：LINE Login 要求使用 HTTPS，所以本地開發仍建議使用 ngrok。

---

## 📋 快速檢查清單

- [ ] ngrok 進程正在運行
- [ ] 複製了新的 ngrok URL
- [ ] 更新了 `config/line.php` 中的 `ngrok_url`
- [ ] 更新了 LINE Developer Console 的 Callback URL
- [ ] Callback URL 格式正確（包含完整路徑）
- [ ] 清除瀏覽器緩存後重新測試

---

## 🔧 故障排除

### 問題 1: 仍然出現 NGROK_3200
- 確認 ngrok 進程沒有關閉
- 確認複製的 URL 沒有包含結尾的斜線
- 重新啟動 XAMPP 的 Apache 服務

### 問題 2: Callback URL 不匹配
確保 LINE Console 中的 Callback URL 與配置文件中的完全一致：
```
[ngrok_url] + [callback_path]
```

### 問題 3: ngrok 顯示 "Visit Site" 頁面
這是 ngrok 的安全提示頁面，點擊 "Visit Site" 按鈕即可繼續。

---

## 📞 需要幫助？

如果問題仍未解決，請提供：
1. ngrok 終端的完整輸出
2. LINE Developer Console 的 Callback URL 設定截圖
3. 瀏覽器訪問時的完整錯誤訊息

---

最後更新：2026-01-08
