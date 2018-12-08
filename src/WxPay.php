<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/2/12 9:29
 */

namespace Payment;

use Payment\Wx\lib\WxPayApi;
use Payment\Wx\lib\JsApiPay;
use Payment\Wx\lib\NativePay;
use Payment\Wx\lib\WxPayRefund;
use Payment\Wx\lib\WxPayOrderQuery;
use Payment\Wx\lib\WxPayRefundQuery;
use Payment\Wx\lib\WxPayUnifiedOrder;
use Payment\Wx\lib\WxPayException;
use Payment\Wx\lib\WxPayResults;

class WxPay
{
    private $config = [
        'appid'           => '',
        'mchid'           => '',
        'key'             => '',
        'secret'          => '',
        'sslcert_path'    => '',
        'sslkey_path'     => '',
        'curl_proxy_host' => '0.0.0.0',
        'curl_proxy_port' => 0,
        'report_level'    => 1,
        'notify_url'      => ''
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    public function jsPay($subject, $out_trade_no, $total_fee, $notify_url, $goods_tag = '', $attach = '')
    {
        $tools  = new JsApiPay($this->config);
        $openId = $tools->GetOpenid();
        $input  = new WxPayUnifiedOrder($this->config['key']);
        $input->SetBody($subject);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($total_fee * 100);
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("JSAPI");
        if ($goods_tag) $input->SetGoods_tag($goods_tag);
        if ($attach) $input->SetAttach($attach);

        $input->SetOpenid($openId);
        $pay             = new WxPayApi($this->config);
        $order           = $pay->unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        return $jsApiParameters;
    }

    public function h5Pay($subject, $out_trade_no, $total_fee, $return_url, $notify_url, $goods_tag = '', $attach = '')
    {
        $input = new WxPayUnifiedOrder($this->config['key']);
        $input->SetBody($subject);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($total_fee * 100);
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("MWEB");
        if ($goods_tag) $input->SetGoods_tag($goods_tag);
        if ($attach) $input->SetAttach($attach);
        $pay    = new WxPayApi($this->config);
        $result = $pay->unifiedOrder($input);
        if ($result['return_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['return_msg']];
        }
        if ($result['result_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['err_code_des']];
        }
        return ['ret' => 1, 'data' => $result['mweb_url'] . '&redirect_url=' . urlencode($return_url)];
    }

    public function qrcodePay($subject, $out_trade_no, $total_fee, $notify_url, $goods_tag = '', $attach = '')
    {
        $input = new WxPayUnifiedOrder($this->config['key']);
        $input->SetBody($subject);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($total_fee * 100);
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id("123456");
        if ($goods_tag) $input->SetGoods_tag($goods_tag);
        if ($attach) $input->SetAttach($attach);

        $tool   = new NativePay();
        $result = $tool->GetPayUrl($input, $this->config);

        if ($result['return_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['return_msg']];
        }
        if ($result['result_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['err_code_des']];
        }
        return ['ret' => 1, 'data' => $result["code_url"]];

    }

    /**
     * @param string $out_trade_no
     * @param string $trade_no 商户订单号、微信交易号 参数二选一
     * @return array
     */
    public function query($out_trade_no = '', $trade_no = '')
    {
        $input = new WxPayOrderQuery($this->config['key']);
        if ($out_trade_no) {
            $input->SetOut_trade_no($out_trade_no);
        } elseif ($trade_no) {
            $input->SetTransaction_id($trade_no);
        } else {
            return ['ret' => 0, 'msg' => '订单号与微信交易号不能同时为空'];
        }
        $tool   = new WxPayApi($this->config);
        $result = $tool->orderQuery($input);

        if ($result['return_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['return_msg']];
        }
        if ($result['result_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['err_code_des']];
        }
        return [
            'ret'  => 1,
            'data' => [
                'attach'           => $result['attach'],//支付时提交的附加数据
                'bank_type'        => $result['bank_type'],//银行类型
                'cash_fee'         => $result['cash_fee'] / 100,//支付金额
                'fee_type'         => $result['fee_type'],//支付币种
                'is_subscribe'     => $result['is_subscribe'],//是否关注公众号
                'openid'           => $result['openid'],//
                'out_trade_no'     => $result['out_trade_no'],//商户订单号
                'time_end'         => $result['time_end'],//支付完成时间戳
                'total_fee'        => $result['total_fee'] / 100,//订单总金额
                'trade_state'      => $result['trade_state'],//交易状态
                'trade_state_desc' => $result['trade_state_desc'],//交易状态描述
                'trade_type'       => $result['trade_type'],//交易类型
                'transaction_id'   => $result['transaction_id'],//交易类型
            ]];
    }

    /**
     * @param $refund_no 同一订单支付多次退款，每次退款需更换不同的退款单号
     * @param $total_fee
     * @param $refund_fee
     * @param string $out_trade_no
     * @param string $trade_no 商户订单号、微信交易号 参数二选一
     * @return array    可分多次退款，最多50次；
     */
    public function refund($refund_no, $total_fee, $refund_fee, $out_trade_no = '', $trade_no = '')
    {
        $input = new WxPayRefund($this->config['key']);
        if ($out_trade_no) {
            $input->SetOut_trade_no($out_trade_no);
        } elseif ($trade_no) {
            $input->SetTransaction_id($trade_no);
        } else {
            return ['ret' => 0, 'msg' => '订单号与微信交易号不能同时为空'];
        }

        $input->SetTotal_fee($total_fee * 100);
        $input->SetRefund_fee($refund_fee * 100);
        $input->SetOut_refund_no($refund_no);
        $tool   = new WxPayApi($this->config);
        $result = $tool->refund($input);

        if ($result['return_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['return_msg']];
        }
        if ($result['result_code'] == 'FAIL') {
            return ['ret' => 0, 'msg' => $result['err_code_des']];
        }
        return ['ret' => 1, 'data' => [
            'out_trade_no'  => $result['out_trade_no'],        //商户订单号码
            'out_refund_no' => $result['out_refund_no'],       //商户提交的退款单号
            'refund_id'     => $result['refund_id'],           //微信退款单号
            'total_fee'     => $result['total_fee'] / 100,     //订单总金额
            'refund_fee'    => $result['refund_fee'] / 100,    //退款金额
        ]];
    }

    /**
     * @param string $out_trade_no
     * @param string $trade_no
     * @param string $out_refund_no
     * @param string $refund_no 四个参数任选一个即可
     * @return array
     */
    public function refundQuery($out_trade_no = '', $trade_no = '', $out_refund_no = '', $refund_no = '')
    {
        $input = new WxPayRefundQuery($this->config['key']);
        if ($out_trade_no) {
            $input->SetOut_trade_no($out_trade_no);
        } elseif ($trade_no) {
            $input->SetTransaction_id($trade_no);
        } elseif ($out_refund_no) {
            $input->SetOut_refund_no($out_refund_no);
        } elseif ($refund_no) {
            $input->SetRefund_id($refund_no);
        } else {
            return ['ret' => 0, 'msg' => '订单号与微信交易号不能同时为空'];
        }

        $tool   = new WxPayApi($this->config);
        $result = $tool->refundQuery($input);
        if ($result['return_code'] == 'FAIL')
            return ['ret' => 0, 'msg' => $result['return_msg']];
        if ($result['result_code'] == 'FAIL')
            return ['ret' => 0, 'msg' => $result['err_code_des']];

        unset(
            $result['appid'],
            $result['mch_id'],
            $result['nonce_str'],
            $result['result_code'],
            $result['return_code'],
            $result['return_msg'],
            $result['sign']
        );
        for ($i = 0; $i < 50; $i++) {
            if (array_key_exists('refund_id_' . $i, $result)) {
                $result['refund_fee_' . $i] /= 100;//单次退款金额
            } else {
                break;
            }
        }
        $result['cash_fee']   /= 100;//订单支付总金额
        $result['refund_fee'] /= 100;//累计退款金额
        return ['ret' => 1, 'data' => $result];
    }

    /**
     * @param $key
     * @return array|bool notify OK后返回验证数组
     */
    public static function checkNotify($key)
    {
        $xml = file_get_contents('php://input');
        //如果返回成功则验证签名
        try {
            $result = WxPayResults::Init($xml, $key);
        } catch (WxPayException $e) {
            $msg = $e->errorMessage();
            return false;
        }
        return ($result);
    }
}