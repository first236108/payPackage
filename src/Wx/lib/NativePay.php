<?php

namespace Payment\Wx\lib;
/**
 *
 * 刷卡支付实现类
 * @author widyhu
 *
 */
class NativePay
{
    /**
     *
     * 生成扫描支付URL,模式一
     * @param BizPayUrlInput $bizUrlInfo
     */
    public function GetPrePayUrl($productId, $config)
    {
        $biz = new WxPayBizPayUrl();
        $biz->SetProduct_id($productId);
        $pay    = new WxPayApi($config);
        $values = $pay->bizpayurl($biz);
        $url    = "weixin://wxpay/bizpayurl?" . $this->ToUrlParams($values);
        return $url;
    }

    /**
     *
     * 参数数组转换为url参数
     * @param array $urlObj
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     *
     * 生成直接支付url，支付url有效期为2小时,模式二
     * @param UnifiedOrderInput $input
     */
    public function GetPayUrl($input, $config)
    {
        if ($input->GetTrade_type() == "NATIVE") {
            $pay    = new WxPayApi($config);
            $result = $pay->unifiedOrder($input);
            return $result;
        }
    }
}