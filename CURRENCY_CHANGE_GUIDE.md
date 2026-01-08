# 貨幣符號修改說明

## ✅ 已完成的修改

已將 ShopXO 系統的貨幣符號從人民幣（￥）改為新台幣（NT$）

### 修改的配置文件

**文件路徑**: `config/shopxo.php`

### 修改內容

| 配置項 | 修改前 | 修改後 |
|-------|-------|-------|
| 貨幣符號 | ￥ | NT$ |
| 貨幣代碼 | RMB | TWD |
| 貨幣名稱 | 人民币 | 新台幣 |
| 匯率 | 0.0000 | 0.0000（保持不變）|

---

## 📋 後續操作

### 1. 清除系統緩存

修改配置後，需要清除系統緩存才能生效：

**方法一：通過後台管理**
1. 登入後台管理系統
2. 進入 **系統 → 緩存管理**
3. 點擊「一鍵清除緩存」

**方法二：手動刪除緩存文件**
```powershell
# 刪除 runtime 目錄下的緩存
Remove-Item -Path "c:\xampp\htdocs\shopxo\runtime\cache\*" -Recurse -Force
```

### 2. 檢查前端顯示

清除緩存後，訪問以下頁面檢查貨幣符號是否已更新：

- ✅ 商品列表頁
- ✅ 商品詳情頁
- ✅ 購物車
- ✅ 訂單結算頁
- ✅ 會員中心
- ✅ 訂單列表

### 3. 檢查後台管理

登入後台管理系統，檢查以下頁面：

- ✅ 訂單管理
- ✅ 商品管理
- ✅ 財務統計
- ✅ 報表數據

### 4. 小程序和 APP 端

如果您使用了小程序或 APP：

1. 小程序需要重新編譯發布
2. APP 需要重新打包
3. 確保前端代碼同步更新

---

## 🔍 驗證貨幣符號是否更新成功

### 方法一：查看商品價格

訪問商城前台，查看商品價格是否顯示為 `NT$` 符號：
```
修改前：￥99.00
修改後：NT$99.00
```

### 方法二：查看購物車

將商品加入購物車，檢查購物車中的金額顯示：
```
修改前：總計：￥199.00
修改後：總計：NT$199.00
```

### 方法三：檢查數據庫（可選）

如果需要檢查訂單歷史記錄的貨幣符號，可以查詢數據庫：

```sql
-- 查看訂單貨幣表
SELECT * FROM sxo_order_currency LIMIT 10;

-- 如需更新歷史訂單的貨幣符號（謹慎操作）
UPDATE sxo_order_currency SET 
    currency_symbol = 'NT$',
    currency_code = 'TWD',
    currency_name = '新台幣'
WHERE currency_code = 'RMB';
```

---

## ⚠️ 注意事項

### 1. 價格不會自動轉換

- 修改貨幣符號**不會改變商品價格數值**
- 如果需要按匯率轉換價格，需要手動更新商品價格
- 或使用批量價格調整功能

### 2. 歷史訂單

- 已生成的訂單可能仍顯示舊的貨幣符號
- 新訂單將自動使用新的貨幣符號

### 3. 支付配置

如果使用在線支付（如微信支付、支付寶）：
- 確認支付接口配置支持新台幣結算
- 或保持使用人民幣結算，僅顯示為新台幣

### 4. 前端編譯文件

系統中的編譯後 JS 文件（public/static 目錄）中可能包含硬編碼的貨幣符號：
- 這些文件在開發模式下會自動重新生成
- 生產環境可能需要重新編譯前端資源

---

## 🛠️ 快速清除緩存命令

在 PowerShell 中執行：

```powershell
# 進入項目目錄
cd c:\xampp\htdocs\shopxo

# 清除所有運行時緩存
Remove-Item -Path "runtime\cache\*" -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path "runtime\admin\temp\*" -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path "runtime\api\temp\*" -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path "runtime\index\temp\*" -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "緩存清除完成！貨幣符號已更新為 NT$" -ForegroundColor Green
```

---

## 📝 需要幫助？

如果修改後貨幣符號沒有更新：

1. ✅ 確認已清除緩存
2. ✅ 刷新瀏覽器（Ctrl + F5 強制刷新）
3. ✅ 檢查瀏覽器控制台是否有 JavaScript 錯誤
4. ✅ 查看 config/shopxo.php 文件確認修改已保存

---

最後更新：2026-01-08
