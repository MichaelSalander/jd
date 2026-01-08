<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Custom
// +----------------------------------------------------------------------
namespace payment;

use app\service\PayLogService;

/**
 * 綠界金流（ECPay）支付
 * @author   Custom
 * @version  1.0.0
 * @date     2026-01-08
 * @desc     支援信用卡、ATM、超商代碼等支付方式
 */
class Ecpay
{
    // 插件配置参数
    private $config;

    /**
     * 构造方法
     * @param   [array]  $params [输入参数（支付配置参数）]
     */
    public function __construct($params = [])
    {
        $this->config = $params;
    }

    /**
     * 配置信息
     */
    public function Config()
    {
        // 基础信息
        $base = [
            'name'           => '綠界金流（ECPay）',
            'version'        => '1.0.0',
            'apply_version'  => '不限',
            'apply_terminal' => ['pc', 'h5', 'ios', 'android'],
            'desc'           => '綠界科技（ECPay）金流服務，支援信用卡、ATM轉帳、超商代碼等多種支付方式。適用於台灣地區電商交易。<a href="https://www.ecpay.com.tw/" target="_blank">立即申請</a>',
            'author'         => 'Devil',
            'author_url'     => 'http://shopxo.net/',
        ];

        // 配置信息
        $element = [
            [
                'element'     => 'input',
                'type'        => 'text',
                'default'     => '',
                'name'        => 'merchant_id',
                'placeholder' => '商店代號（Merchant ID）',
                'title'       => '商店代號',
                'is_required' => 1,
                'message'     => '請填寫綠界商店代號',
            ],
            [
                'element'     => 'input',
                'type'        => 'text',
                'default'     => '',
                'name'        => 'hash_key',
                'placeholder' => 'HashKey',
                'title'       => 'HashKey',
                'is_required' => 1,
                'message'     => '請填寫HashKey',
            ],
            [
                'element'     => 'input',
                'type'        => 'text',
                'default'     => '',
                'name'        => 'hash_iv',
                'placeholder' => 'HashIV',
                'title'       => 'HashIV',
                'is_required' => 1,
                'message'     => '請填寫HashIV',
            ],
            [
                'element'     => 'select',
                'title'       => '環境設定',
                'message'     => '請選擇環境',
                'name'        => 'environment',
                'is_required' => 0,
                'element_data' => [
                    ['value' => 'test', 'name' => '測試環境'],
                    ['value' => 'production', 'name' => '正式環境'],
                ],
            ],
            [
                'element'     => 'select',
                'title'       => '支付方式',
                'message'     => '請選擇支付方式',
                'name'        => 'payment_type',
                'is_required' => 0,
                'element_data' => [
                    ['value' => 'ALL', 'name' => '不指定付款方式（讓用戶選擇）'],
                    ['value' => 'Credit', 'name' => '信用卡'],
                    ['value' => 'WebATM', 'name' => 'WebATM'],
                    ['value' => 'ATM', 'name' => 'ATM轉帳'],
                    ['value' => 'CVS', 'name' => '超商代碼'],
                    ['value' => 'BARCODE', 'name' => '超商條碼'],
                ],
            ],
        ];

        return [
            'base'    => $base,
            'element' => $element,
        ];
    }

    /**
     * 支付入口
     * @param   [array]  $params [输入参数]
     */
    public function Pay($params = [])
    {
        // 参数验证
        if(empty($params))
        {
            return DataReturn('參數不能為空', -1);
        }
        
        // 配置信息验证
        if(empty($this->config) || empty($this->config['merchant_id']) || empty($this->config['hash_key']) || empty($this->config['hash_iv']))
        {
            return DataReturn('支付缺少配置，請檢查商店代號、HashKey、HashIV是否正確填寫', -1);
        }

        // 商品名稱處理（綠界ItemName限制400字元）
        $item_name = mb_substr($params['name'], 0, 50, 'UTF-8');
        
        // 支付参数
        $order = [
            'MerchantID'        => $this->config['merchant_id'],
            'MerchantTradeNo'   => $params['order_no'],
            'MerchantTradeDate' => date('Y/m/d H:i:s'),
            'PaymentType'       => 'aio',
            'TotalAmount'       => (int)$params['total_price'],
            'TradeDesc'         => '訂單付款',
            'ItemName'          => $item_name,
            'ReturnURL'         => $params['notify_url'],
            'ClientBackURL'     => $params['call_back_url'],
            'OrderResultURL'    => $params['call_back_url'],
            'ChoosePayment'     => empty($this->config['payment_type']) ? 'ALL' : $this->config['payment_type'],
            'EncryptType'       => 1,
            'NeedExtraPaidInfo' => 'N',
        ];

        // 生成檢查碼
        $order['CheckMacValue'] = $this->GenerateCheckMacValue($order);

        // 支付请求记录
        PayLogService::PayLogRequestRecord($params['order_no'], ['request_params' => $order]);

        // 根據環境選擇網址
        $api_url = $this->GetApiUrl();

        // 生成表單
        $html = $this->BuildRequestForm($api_url, $order);

        // app接口則返回數據
        if(APPLICATION == 'app')
        {
            $result = [
                'data' => $order,
                'html' => $html,
                'url'  => $api_url,
            ];
            return DataReturn('success', 0, $result);
        }

        // web端輸出執行form表單post提交
        exit($html);
    }

    /**
     * 支付回调处理
     * @param   [array]  $params [输入参数]
     */
    public function Respond($params = [])
    {
        $data = empty($_POST) ? $_GET : array_merge($_GET, $_POST);

        // 驗證檢查碼
        if(empty($data['CheckMacValue']))
        {
            return DataReturn('缺少檢查碼', -1);
        }

        $check_mac_value = $data['CheckMacValue'];
        unset($data['CheckMacValue']);

        // 重新計算檢查碼
        $generated_check = $this->GenerateCheckMacValue($data);

        if($check_mac_value !== $generated_check)
        {
            return DataReturn('檢查碼驗證失敗', -1);
        }

        // 檢查支付狀態
        if(isset($data['RtnCode']) && $data['RtnCode'] == '1')
        {
            return DataReturn('支付成功', 0, $this->ReturnData($data));
        }

        $error_msg = isset($data['RtnMsg']) ? $data['RtnMsg'] : '支付失敗';
        return DataReturn($error_msg, -100);
    }

    /**
     * 返回数据统一格式
     * @param   [array]  $data [返回数据]
     */
    private function ReturnData($data)
    {
        return [
            'trade_no'      => isset($data['TradeNo']) ? $data['TradeNo'] : '',
            'buyer_user'    => isset($data['MerchantID']) ? $data['MerchantID'] : '',
            'out_trade_no'  => $data['MerchantTradeNo'],
            'subject'       => isset($data['ItemName']) ? $data['ItemName'] : '',
            'pay_price'     => isset($data['TradeAmt']) ? $data['TradeAmt'] : (isset($data['TotalAmount']) ? $data['TotalAmount'] : 0),
        ];
    }

    /**
     * 退款处理
     * @param   [array]  $params [输入参数]
     */
    public function Refund($params = [])
    {
        // 参数验证
        $p = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'order_no',
                'error_msg'    => '訂單號不能為空',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'trade_no',
                'error_msg'    => '交易平台訂單號不能為空',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'refund_price',
                'error_msg'    => '退款金額不能為空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 綠界退款需要另外申請API權限
        // 退款參數
        $refund_data = [
            'MerchantID'       => $this->config['merchant_id'],
            'MerchantTradeNo'  => $params['order_no'],
            'TradeNo'          => $params['trade_no'],
            'Action'           => 'R',
            'TotalAmount'      => (int)$params['refund_price'],
        ];

        $refund_data['CheckMacValue'] = $this->GenerateCheckMacValue($refund_data);

        // 退款API URL
        $api_url = $this->GetApiUrl('refund');

        // 執行請求
        $result = $this->HttpRequest($api_url, $refund_data);

        if($result && isset($result['RtnCode']) && $result['RtnCode'] == '1')
        {
            $data = [
                'out_trade_no'   => $params['order_no'],
                'trade_no'       => $params['trade_no'],
                'refund_price'   => $params['refund_price'],
                'return_params'  => $result,
                'request_params' => $refund_data,
            ];
            return DataReturn('退款成功', 0, $data);
        }

        $error_msg = isset($result['RtnMsg']) ? $result['RtnMsg'] : '退款失敗';
        return DataReturn($error_msg, -1000);
    }

    /**
     * 獲取API網址
     * @param   [string]  $type [API類型：payment/refund/query]
     */
    private function GetApiUrl($type = 'payment')
    {
        $is_production = isset($this->config['environment']) && $this->config['environment'] == 'production';

        $urls = [
            'payment' => [
                'test'       => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
                'production' => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
            ],
            'refund' => [
                'test'       => 'https://payment-stage.ecpay.com.tw/CreditDetail/DoAction',
                'production' => 'https://payment.ecpay.com.tw/CreditDetail/DoAction',
            ],
            'query' => [
                'test'       => 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
                'production' => 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
            ],
        ];

        $env = $is_production ? 'production' : 'test';
        return $urls[$type][$env];
    }

    /**
     * 生成檢查碼（CheckMacValue）
     * 參考綠界官方文檔: https://developers.ecpay.com.tw/?p=2902
     * @param   [array]  $params [參數陣列]
     */
    private function GenerateCheckMacValue($params)
    {
        // 1. 移除CheckMacValue（若存在）
        unset($params['CheckMacValue']);

        // 2. 按照鍵值A-Z排序
        ksort($params);

        // 3. 組合字串：HashKey開頭，參數用&串接，HashIV結尾
        $check_str = 'HashKey=' . $this->config['hash_key'];
        foreach($params as $key => $value)
        {
            // 空值不參與檢查碼計算
            if($value !== '' && $value !== null)
            {
                $check_str .= '&' . $key . '=' . $value;
            }
        }
        $check_str .= '&HashIV=' . $this->config['hash_iv'];

        // 4. 進行URL encode
        $check_str = urlencode($check_str);

        // 5. 轉小寫
        $check_str = strtolower($check_str);

        // 6. 將特定字符還原（依照綠界規範）
        // 還原: - _ . ! * ( )
        $check_str = str_replace('%2d', '-', $check_str);
        $check_str = str_replace('%5f', '_', $check_str);
        $check_str = str_replace('%2e', '.', $check_str);
        $check_str = str_replace('%21', '!', $check_str);
        $check_str = str_replace('%2a', '*', $check_str);
        $check_str = str_replace('%28', '(', $check_str);
        $check_str = str_replace('%29', ')', $check_str);

        // 7. SHA256加密
        $check_mac_value = hash('sha256', $check_str);

        // 8. 轉大寫並返回
        return strtoupper($check_mac_value);
    }

    /**
     * 建立請求表單
     * @param   [string]  $url    [API網址]
     * @param   [array]   $params [參數]
     */
    private function BuildRequestForm($url, $params)
    {
        $html = "<form id='ecpaysubmit' name='ecpaysubmit' action='".$url."' method='POST'>";
        foreach($params as $key => $val)
        {
            if(!empty($val) || $val === 0)
            {
                $val = str_replace("'", "&apos;", $val);
                $html .= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }
        $html .= "<input type='submit' value='確認付款' style='display:none;'></form>";
        $html .= "<script>document.forms['ecpaysubmit'].submit();</script>";
        return $html;
    }

    /**
     * HTTP請求
     * @param   [string]  $url  [請求URL]
     * @param   [array]   $data [發送數據]
     */
    private function HttpRequest($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);
        
        if(curl_errno($ch))
        {
            curl_close($ch);
            return false;
        }

        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($httpStatusCode !== 200)
        {
            return false;
        }

        // 解析回傳數據
        parse_str($response, $result);
        return $result;
    }

    /**
     * 訂單自動關閉的時間
     */
    public function OrderAutoCloseTime()
    {
        return intval(MyC('common_order_close_limit_time', 30, true)) . 'm';
    }
}
?>
