<?php

namespace App\Entities\Common\Helpers;


class JuheHelper
{
    /*
     * 发送聚合短信
     * @param $mobile
     * @param $code
     * @return bool|string
     */
    public static function sendJuheCode($mobile, $code)
    {
        $smsCfg = [
            'key' => '8ae44179a666cefff0cc0e6ddee49dc2',
            'mobile' => $mobile,
            'tpl_id' => '165170',
            'tpl_value' => '#code#=' . $code,
        ];
        $juheUrl = 'http://v.juhe.cn/sms/send';
        $content = self::curl($juheUrl, $smsCfg, 1);
        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                return true;
            } else {
                //状态非0，说明失败
                return $error_code;
            }
        } else {
            //返回内容异常，以下可根据业务逻辑自行修改
            return '请求发送短信失败';
        }
    }

    /**
     * 请求接口返回内容
     * @param  string $url [请求的URL地址]
     * @param  string $params [请求的参数]
     * @param  int $ipost [是否采用POST形式]
     * @return  string
     */
    public static function curl($url, $params = false, $ispost = 0)
    {

        if ($ispost){
            $result = \helper::httpPostContents($url,$params);
            return $result;
        }

        $httpInfo = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        $response = curl_exec($ch);
        if ($response === false) {
            return false;
        }
        curl_close($ch);
        return $response;
    }
}
