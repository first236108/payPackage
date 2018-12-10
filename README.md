# payPackage
the weixinPay and alipay package
# for install
composer require first236108/payment

#example
```php
use Payment\WxPay;
use Payment\AliPay;

class Index
{
    public function index()
        {
            $return_url   = 'http://igccc.com';
            $notify_url   = 'http://igccc.com/notify';
            $subject      = '测试交易标题';
            $totle_fee    = 0.01;//单位：元
            $out_trade_no = '20181208' . mt_rand(1000, 9999);//商户订单号
            $refund_no    = '20181208' . mt_rand(1000, 9999);//退款单号
            //传递此参数为数组时，返回二维码图片base64数据，不传返回付款url,数组可选内容为['size'=>3,'level'=>0,'margin'=>4] size二维码大小,level二维码容错等级0~4,margin图片距离边缘的margin
            $build_png    = [];
            
            #微信
            $wx_config = [
                'appid'        => 'wxd61e5ed1a1234567',
                'mchid'        => '1481234567',
                'key'          => 'bb17cd46792e4be49048809661234567',
                'secret'       => 'cdcb1b56897754fb0459ba9621234567',
                'sslcert_path' => env('config_path') . 'apiclient_cert.pem',//绝对路径
                'sslkey_path'  => env('config_path') . 'apiclient_key.pem',//绝对路径
            ];
    
            $pay = new WxPay($wx_config);
            #APP支付
            //$res = $pay->app($subject, $out_trade_no, $totle_fee, $notify_url);
            #微信内支付
            //$res = $pay->jsPay($subject, $out_trade_no, $totle_fee, $notify_url);
            # h5支付，url需自行跳转
            //$res = $pay->h5Pay($subject, $out_trade_no, $totle_fee, $return_url, $notify_url);
            # 订单收款二维码,$build_png为可选参数
            //$res = $pay->qrcodePay($subject, $out_trade_no, $totle_fee, $return_url, $build_png);
            #订单查询
            //$res = $pay->query($out_trade_no);
            #退款，第一个参数为退款单号，第二个参数为订单总金额，第三个参数为本次退款金额，第四(商户订单号)第五(微信订单号)参数任选其一，支持一个订单分多次退款。
            //$res = $pay->refund($refund_no, $totle_fee, 10, $out_trade_no，'');
            #退款查询
            //$res = $pay->refundQuery($refund_no);
    
            #异步回调,notify验签成功后会执行updatePayStatus($arr)，成功更新支付结果后务必返回true，以便反馈微信平台，注意判断重复通知、并发回调
            $pay->notify('updatePayStatus');
    
    
            #支付宝
            $out_biz_no = '20181208' . mt_rand(1000, 9999);//商户转账订单号
            $ali_config     = [
                'app_id'               => "2016101001234567",
                'merchant_private_key' => "MIIEpQIBAAKCAQEw1NVUeUQIvZsnDaG9OnTZILolKN5qkP3pNl3qsUx+d3saXgsylG10m+0Fa6Hgrs6vhJnw8CJBcwAyDMsfGheveulXaWZIq/ZZZeWbabAxFygc5vZvKqfau7HgOOLZxfEEYn3SeGykUQhH2005xQIK0cDh7cMQuyc/0HXLyj5FuwZqVuxdH9TfpC/x5jNhXSD6XWNzDFupglyJD8ufq+4yhY8ZF1u/QAyRwIDAQABAoIBAQCaI4AcNiUtF5TNhRcd2pW6kYKL0i3cI2OCzMYNWzO2K0OLGM28aigyNWpKi2MxTs9ksz8B++9w5UJRMvc5bEdWggsjzZI8emv3plBB13V3/SzeKvgQ+wFUm4tqt7Iv5/SdmT30ZRt/fESK7W9vGYGH5QuypKm8R/AtSz6F/XDt34tME2g0W4GL0g2xMUBgSKsSQmOhL3a4VOxz063Q8Cu6E5bL8Au/plo45Ru4fpFbghAW4+JgDWvryC1d/WqjgjiqrDzbff8ZcP+26J0qyV8usRJ5Zg03KAofayTxW+sCmi+lmyfc1fcMjO3qFpEADNT9VyqtLjlaX09SZnt9vVPBAoGBANjWnYpkEM/Q7rvMemTf7TxOry54RZQ4o8vi0J5q71jK+1QcLkna8l/70HdNR9YoYYWh/5X6CNxjhc5I5LgHh68C/5GDKizZ2asEbhKK7fB5yptUVSqS3mYa/m4udjxOdtNHHYpI63jwPPF0wzhjrtS/ZUhVRS5Fk+387g22WnzxAoGBAMu/jHp5jr4PruaVTYjLJmHmazQeWJX8mcwX8ik9m7EdMHuYlJZv/yvEV8WjHVyIpPsnwySZYWPLkDjBrq5Y3w6cAkLwDhfZosSH1QP0lVMVG9bpaApSqYQLY57M8ALbgxRu3sM/51hor1dXH0zOcFIJjrfTvlycR9VjzBKuxwK3AoGBAIvbMNV34RgI7FIYesDhZURGg9r3G/mT5qG0c1YaJrIgiaYgXwKnwziBVF3+bF0Gwo/Mgusaz+hGEKGShmLkCHGq/2e9L9Cp2ijhvJUdIoa+Jx20cRH6lJNKydwK/5u6CA4Rik4M5kOkv8oEw311Xeg+Ynca+Hn1yonvXyNdAFQhAoGAYL74pmM3+1mZFBZK9ax48j2mI95Q9A84vCeWrix51DTnA5kk3PYbNR4LC2Zzl6+uny93Qtat3uQW/ExDdLfwWpCLpls0ZfDKkAVriXBGw2efi4HTDCvKIFAEIrBvOvwelsI6dn3OjTVQJOnSi/bucJXnNbSOjI0Msu+rRCfHiasCgYEAt2dlRaoKfuFH/ShdnkhthBDG7RiR6c0iOrcgm7cA0p23bPo6oZfwW1CT0Hz2tdeQLIY89RwZ0aPPUcAI4dpGOt27lFzFKCIBFcJHd1X6qcJh0PWaooxq1ryamJ9rUBbwCJ9C54t9QpauoBSfnBrEGttGtY/qFV4aepLbplgY1jw=",
                'notify_url'           => "",
                'return_url'           => "",
                'alipay_public_key'    => "MIIBIjANBgkqhkiHSI8Q9S+OmXsJ++SB3Gl29yeMpjRImNM3dRLk/ffvtLRbYpzBegIR00+ltuz580R+kesdJRivAKDovsSlF6muyeuZJK3sklf1KOKqaeeSebO8TLWh8rtRww/BXMG7ydaDswYXhdlv7/IlDKHRMhmssY1s2NczsZ/hsEytdzDJprvlN0yKGApTWmOMTuuO8KSJqae2nI8AGa9/dFhyFT2psw+GeXFF3ZIZWLwCW6VClX5KDofn/v+1RHtIsIAyRLuvDyAmhNym56XIIupfJ+ezewIDAQAB",
            ];
    
            $pay = new AliPay($ali_config);
            #APP支付，调支付宝app的参数 String
            //$res = $pay->app($subject, $out_trade_no, $totle_fee, $notify_url);
            #手机网页支付，直接跳转，会自动尝试调起支付宝app
            //$pay->h5Pay($subject, $out_trade_no, $totle_fee, $return_url, $notify_url);
            # pc网页支付，可扫码或登录支付宝账号完成支付流程
            //$pay->pagePay($subject, $out_trade_no, $totle_fee, $return_url, $notify_url);
            #当面付，根所订单生成收款二维码,$build_png为可选参数
            //$res = $pay->qrPay($subject, $out_trade_no, $totle_fee, $notify_url, $build_png);
            #单笔转账,第二个参数为账户类型，它指明收款人$payee_account的类型，0代表$payee_account是userId,1代表$payee_account是支付宝登录用户名
            //$res = $pay->transToAccount($out_biz_no, 0, $payee_account, $totle_fee);
            #退款,$out_trade_no或支付宝订单号$trade_no二选一即可；
            //$res = $pay->refund($refund_no, $totle_fee, $out_trade_no, $trade_no);
            #交易订单查询，商户订单号、支付宝交易号 参数二选一即可
            //$res = $pay->query($out_trade_no, $trade_no);
            #退款查询，商户订单号、支付宝交易号 参数二选一即可
            //$res = $pay->refundQuery($out_trade_no, $trade_no);
    
            //验签成功后会执行updatePayStatus($arr),成功更新支付结果后务必返回true,以便告知支付宝,注意判断重复通知、并发回调
            $pay->notify('updatePayStatus');
    
            dump($res);
            die;
        }
}
```
