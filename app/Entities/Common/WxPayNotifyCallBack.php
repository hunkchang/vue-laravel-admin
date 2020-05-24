<?php
/**
 *
 * @author zhangdawei
 * @web    http://www.bitzhu.com
 * 张大为:328231840@qq.com
 * @time 15:43
 */

namespace  Models\Common;

use Latrell\Wxpay\Models\OrderQuery;
use Latrell\Wxpay\Sdk\Api;
use Latrell\Wxpay\Sdk\Notify;


class WxPayNotifyCallBack extends Notify
{
    protected $notifyData = array();
    public function __construct()
    {
        $config = \config('latrell-wxpay');
        parent::__construct($config);
    }
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new OrderQuery();
        $input->setTransactionId($transaction_id);
        $api = new WxApi();
        $result = $api->orderQuery($input);
        \Log::alert(json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }
    public function getNotifyData(){
        return $this->notifyData;
    }
    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        $notfiyOutput = array();
        \Log::alert("call back:" . json_encode($data));
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }

        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
        $this->notifyData = $data;
        return true;
    }
}