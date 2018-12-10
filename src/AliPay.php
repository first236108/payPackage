<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/3/10 10:01
 */

namespace Payment;

use Payment\Ali\Aop\request\AlipayTradeFastpayRefundQueryRequest;
use Payment\Ali\Service\AlipayTradeService;
use Payment\Ali\Aop\request\AlipayTradeAppPayRequest;
use Payment\Ali\Buildermodel\AlipayTradeWapPayContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradePagePayContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeQueryContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeRefundContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeFastpayRefundQueryContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradeFundTransToaccountContentBuilder;
use Payment\Ali\Buildermodel\AlipayTradePrecreateContentBuilder;

class AliPay
{
    private $config = [
        'app_id'               => "",
        //商户私钥，您的原始格式RSA私钥
        'merchant_private_key' => "",
        //异步通知地址
        'notify_url'           => "",
        //同步跳转
        'return_url'           => "",
        //编码格式
        'charset'              => "UTF-8",
        //签名方式
        'sign_type'            => "RSA2",
        //支付宝网关
        'gatewayUrl'           => "https://openapi.alipay.com/gateway.do",
        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key'    => "",
        //最大查询重试次数
        'MaxQueryRetry'        => "10",
        //查询间隔
        'QueryDuration'        => "3"
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    public function app($subject, $out_trade_no, $total_fee, $notify_url = '')
    {
        $order   = [
            'subject'      => $subject,
            'out_trade_no' => $out_trade_no,
            'total_amount' => $total_fee,
            'product_code' => 'QUICK_MSECURITY_PAY'
        ];
        $builder = new AlipayTradeAppPayRequest();
        if ($notify_url)
            $builder->setNotifyUrl($notify_url);
        else
            $builder->setNotifyUrl($this->config['notify_url']);
        $builder->setBizContent(json_encode($order));
        $service  = new AlipayTradeService($this->config);
        $response = $service->app($builder, $notify_url);
        return $response;
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
        //$timeout_express = "10m";
        $builder = new AlipayTradeWapPayContentBuilder();
        $builder->setBody($subject);
        $builder->setSubject($subject);
        $builder->setOutTradeNo($out_trade_no);
        $builder->setTotalAmount($total_fee);
        //$builder->setTimeExpress($timeout_express);
        $pay = new AlipayTradeService($this->config);
        $res = $pay->wapPay($builder, $return_url, $notify_url);
        exit();//跳转
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
        $result = $pay->pagePay($builder, $return_url, $notify_url);
        exit();//跳转
    }

    public function qrPay($subject, $out_trade_no, $total_fee, $notify_url, $build_png = false, $timeExpress = "120m")
    {
        $builder = new AlipayTradePrecreateContentBuilder();
        $builder->setOutTradeNo($out_trade_no);
        $builder->setTotalAmount($total_fee);
        $builder->setTimeExpress($timeExpress);
        $builder->setSubject($subject);
        $builder->setNotifyUrl($notify_url);
        $builder->setDisablePayChinnels('pcredit,pcreditpayInstallment,creditCard,creditCardExpress,creditCardCartoon');
        $qrPay  = new AlipayTradeService($this->config);
        $result = $qrPay->qrPay($builder);
        $res    = $result->getResponse();
        if ($result->getTradeStatus() !== 'SUCCESS')
            return ['ret' => 0, 'msg' => $res];
        $url = $res->qr_code;
        if (is_array($build_png))
            $url = QRcode::png($url, true, $build_png['level'] ?? QR_ECLEVEL_L, $build_png['size'] ?? 3, $build_png['margin'] ?? 4);

        return ['ret' => 1, 'result' => $url];
    }

    /**
     * 单笔转账到支付宝账户
     * @param $out_biz_no
     * @param int $payee_type 0,1
     * @param $payee_account
     * @param $amount
     * @param string $payer_show_name
     * @param string $payee_real_name
     * @param string $remark
     * @return array|mixed
     */
    public function transToAccount($out_biz_no, $payee_type, $payee_account, $amount, $payer_show_name = '', $payee_real_name = '', $remark = '')
    {
        $type    = [0 => 'ALIPAY_USERID', 1 => 'ALIPAY_LOGONID'];
        $builder = new AlipayTradeFundTransToaccountContentBuilder();
        $builder->setOutBizNo($out_biz_no);
        $builder->setPayeeType($type[$payee_type]);
        $builder->setPayeeAccount($payee_account);
        $builder->setAmount($amount);
        if ($payer_show_name) $builder->setPayerShowName($payer_show_name);
        if ($payee_real_name) $builder->setPayeeRealName($payee_real_name);
        if ($remark) $builder->setRemark($remark);
        $pay    = new AlipayTradeService($this->config);
        $result = $pay->transfer($builder);
        $res    = $result->alipay_fund_trans_toaccount_transfer_response;
        if ($res->code != 10000)
            return ['ret' => 0, 'msg' => $res->sub_msg];
        return ['ret' => 1, 'result' => json_decode(json_encode($res), true)];
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
            return ['ret' => 0, 'msg' => $result->sub_msg];
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
        $result = $result->alipay_trade_fastpay_refund_query_response;
        if ($result->code != '10000')
            return ['ret' => 0, 'msg' => $result->msg . $result->sub_msg];
        return ['ret' => 1, 'msg' => json_decode(json_encode($result), true)];
    }

    public function notify($func)
    {
        if (empty($_POST)) {
            exit('回调内容为空');
        }

        $service = new AlipayTradeService($this->config);
        $result  = $service->check($_POST);
        if ($result) {
            if ($_POST['trade_status'] == 'TRADE_SUCCESS' || $_POST['trade_status'] == 'TRADE_FINISHED')
                $res = call_user_func($func, $_POST);
            if (true === $res)
                exit('success');
        }
        exit('fail');
    }
}