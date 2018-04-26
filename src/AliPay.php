<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/3/10 10:01
 */

namespace Payment;
require_once 'autoload.php';

use Payment\Ali\Aop\request\AlipayTradeFastpayRefundQueryRequest;
use Payment\Ali\Service\AlipayTradeService;
use Payment\Ali\Buildermodel\AlipayTradeWapPayContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradePagePayContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeQueryContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeRefundContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeFastpayRefundQueryContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeFundTransToaccountContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradePrecreateContentBuilder;

class AliPay
{
    private $config = [];

    public function __construct()
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Ali' . DIRECTORY_SEPARATOR . 'config.php';
        $this->config = $config;
    }

    /**
     * 手机H5支付
     * @param $subject
     * @param $out_trade_no
     * @param $total_fee
     * @param $return_url
     * @param $notify_url
     * @param string $goods_tag
     * @param string $attach
     */
    public function h5Pay($subject, $out_trade_no, $total_fee, $return_url, $notify_url, $goods_tag = '', $attach = '')
    {
        $timeout_express = "10m";
        $builder         = new AlipayTradeWapPayContentBuilder();
        $builder->setBody($subject);
        $builder->setSubject($subject);
        $builder->setOutTradeNo($out_trade_no);
        $builder->setTotalAmount($total_fee);
        $builder->setTimeExpress($timeout_express);
        $pay    = new AlipayTradeService($this->config);
        $result = $pay->wapPay($builder, $return_url, $notify_url);
        return;
    }

    /**
     * 电脑网页支付
     * @param $subject
     * @param $out_trade_no
     * @param $total_fee
     * @param $return_url
     * @param $notify_url
     * @param string $goods_tag
     * @param string $attach
     */
    public function pagePay($subject, $out_trade_no, $total_fee, $return_url, $notify_url, $goods_tag = '', $attach = '')
    {
        $builder = new AlipayTradePagePayContentBuilder();
        $builder->setBody($subject);
        $builder->setSubject($subject);
        $builder->setTotalAmount($total_fee);
        $builder->setOutTradeNo($out_trade_no);
        $pay    = new AlipayTradeService($this->config);
        $result = $pay->wapPay($builder, $return_url, $notify_url);
        return;
    }

    public function qrPay($subject, $out_trade_no, $total_fee, $notify_url, $timeExpress = "120m")
    {
        $builder=new AlipayTradePrecreateContentBuilder();
        $builder->setOutTradeNo($out_trade_no);
        $builder->setTotalAmount($total_fee);
        $builder->setTimeExpress($timeExpress);
        $builder->setSubject($subject);
        $builder->setNotifyUrl($notify_url);
        $builder->setDisablePayChinnels('pcredit,pcreditpayInstallment,creditCard,creditCardExpress,creditCardCartoon');
        $qrPay = new AlipayTradeService($this->config);
        $result = $qrPay->qrPay($builder);
        if (!isset($result->code))
            return ['ret' => 0, 'msg' => '提现申请失败'];
        if ($result->code != '10000')
            return ['ret' => 0, 'msg' => $result->msg . $result->sub_msg];
        return json_decode(json_encode($result), true);
    }

    /**
     * 单笔转账到支付宝账户
     * @param $out_biz_no
     * @param $payee_type
     * @param $payee_account
     * @param $amount
     * @param string $payer_show_name
     * @param string $payee_real_name
     * @param string $remark
     * @return array|mixed
     */
    public function transToAccount($out_biz_no, $payee_type, $payee_account, $amount, $payer_show_name = '', $payee_real_name = '', $remark = '')
    {
        $builder = new AlipayTradeFundTransToaccountContentBuilder();
        $builder->setOutBizNo($out_biz_no);
        $builder->setPayeeType($payee_type);
        $builder->setPayeeAccount($payee_account);
        $builder->setAmount($amount);
        if ($payer_show_name) $builder->setPayerShowName($payer_show_name);
        if ($payee_real_name) $builder->setPayeeRealName($payee_real_name);
        if ($remark) $builder->setRemark($remark);
        $pay = new AlipayTradeService($this->config);
        $result=$pay->transfer($builder);
        if (!isset($result->code))
            return ['ret' => 0, 'msg' => '提现申请失败'];
        if ($result->code != '10000')
            return ['ret' => 0, 'msg' => $result->msg . $result->sub_msg];
        return json_decode(json_encode($result), true);
    }

    /**
     * 退款
     * @param $refund_no    同一订单支付多次退款，每次退款需更换不同的退款单号
     * @param $refund_fee
     * @param string $out_trade_no
     * @param string $trade_no 商户订单号、支付宝交易号 参数二选一
     * @return array|mixed
     */
    public function refund($refund_no, $refund_fee, $out_trade_no = '', $trade_no = '')
    {
        $builder = new AlipayTradeRefundContentBuilder();
        if ($out_trade_no) {
            $builder->setOutTradeNo($out_trade_no);
        } elseif ($trade_no) {
            $builder->setOutTradeNo($trade_no);
        } else {
            return ['ret' => 0, 'msg' => '订单号与微信交易号不能同时为空'];
        }
        $builder->setRefundAmount($refund_fee);
        $builder->setOutRequestNo($refund_no);
        $aop    = new AlipayTradeService($this->config);
        $result = $aop->Refund($builder);
        if (!isset($result->code))
            return ['ret' => 0, 'msg' => '退款申请失败'];
        if ($result->code != '10000')
            return ['ret' => 0, 'msg' => $result->msg . $result->sub_msg];
        return json_decode(json_encode($result), true);
    }

    /**
     * 交易订单查询
     * @param string $out_trade_no
     * @param string $trade_no 商户订单号、支付宝交易号 参数二选一
     * @return array|mixed
     */
    public function query($out_trade_no = '', $trade_no = '')
    {
        $builder = new AlipayTradeQueryContentBuilder();
        if ($out_trade_no) {
            $builder->setOutTradeNo($out_trade_no);
        } elseif ($trade_no) {
            $builder->setTradeNo($trade_no);
        } else {
            return ['ret' => 0, 'msg' => '订单号与微信交易号不能同时为空'];
        }
        $aop    = new AlipayTradeService($this->config);
        $result = $aop->Query($builder);
        if (!isset($result->code))
            return ['ret' => 0, 'msg' => '查询失败'];
        if ($result->code != '10000')
            return ['ret' => 0, 'msg' => $result->msg];
        return json_decode(json_encode($result), true);
    }

    /**
     * 退款查询
     * @param string $out_trade_no
     * @param string $trade_no 商户订单号、支付宝交易号 参数二选一
     * @param string $out_refund_no 申请退款时的单号，如退款时未传，则填写支付宝交易号，必传;
     * @param string $refund_no
     * @return array
     */
    public function refundQuery($out_trade_no = '', $trade_no = '', $out_refund_no = '', $refund_no = '')
    {
        $builder = new AlipayTradeFastpayRefundQueryContentBuilder();
        if ($out_trade_no) {
            $builder->setOutTradeNo($out_trade_no);
        } elseif ($trade_no) {
            $builder->setOutTradeNo($trade_no);
        } else {
            return ['ret' => 0, 'msg' => '订单号与支付宝交易号不能同时为空'];
        }
        if ($out_refund_no) {
            $builder->setOutRequestNo($out_refund_no);
        } elseif ($refund_no) {
            $builder->setOutRequestNo($refund_no);
        } else {
            return ['ret' => 0, 'msg' => '退款单号与支付宝退款交易号不能同时为空'];
        }
        $aop    = new AlipayTradeService($this->config);
        $result = $aop->refundQuery($builder);
        if (!isset($result->alipay_trade_fastpay_refund_query_response->code))
            return ['ret' => 0, 'msg' => '查询失败'];
        if ($result->alipay_trade_fastpay_refund_query_response->code != '10000')
            return ['ret' => 0, 'msg' => $result->alipay_trade_fastpay_refund_query_response->msg . $result->alipay_trade_fastpay_refund_query_response->sub_msg];
        return ['ret' => 1, 'msg' => $result->alipay_trade_fastpay_refund_query_response->msg];//json_decode(json_encode($result), true);
    }
}