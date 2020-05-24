<?php
/******************************************************************************
 * Filename       : \App\Common\Util\Webcsms.class.php
 * Author         : SouthBear QQ：43930409
 * Email          : SouthBear819@163.com
 * Date/time      : 2018-01-10 19:40:46
 * Purpose        : 网建短信接口类库
 * Modify         :
 ******************************************************************************/

namespace App\Entities\Common\Helpers;

class Webcsms
{
    protected $notify_url = 'http://utf8.api.smschinese.cn/';
    protected $config     = [];

    public function __construct( $config = [] )
    {
        $this->appid  = $config[ 'app_key' ];
        $this->appkey = $config[ 'app_secret' ];
        //$this->appid =  'ih8888';
        //$this->appkey = 'd41d8cd98f00b204e980';
    }

    public function send( $mobile , $content )
    {
        $paras             = [];
        $paras[ 'Uid' ]    = $this->appid;
        $paras[ 'Key' ]    = $this->appkey;
        $paras[ 'smsMob' ] = $mobile;
        // $paras['smsText'] = "【智能云矿机】 验证码：".$content."，30分钟内有效，如非本人操作，请忽略。";
        $paras[ 'smsText' ] = $content;

        //$sign = $this->_hmac_encode($paras,$this->appkey);
        //$paras['sign'] = $sign;
        $qstring  = $this->create_url ( $paras );
        $rtn_code = $this->curl ( 'post' , $this->notify_url , $qstring );
        if ( $rtn_code > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * curl请求
     */
    public function curl( $method , $bgUrl , $qstring )
    {
        $ch = curl_init ();
        curl_setopt ( $ch , CURLOPT_FAILONERROR , false );
        //https 请求
        if ( strlen ( $bgUrl ) > 5 && strtolower ( substr ( $bgUrl , 0 , 5 ) ) == "https" ) {
            curl_setopt ( $ch , CURLOPT_SSL_VERIFYPEER , false );
            curl_setopt ( $ch , CURLOPT_SSL_VERIFYHOST , false );
        }
        if ( strtolower ( $method ) == 'get' ) {
            curl_setopt ( $ch , CURLOPT_URL , $bgUrl . '?' . $qstring );
        } else {
            curl_setopt ( $ch , CURLOPT_URL , $bgUrl );
            curl_setopt ( $ch , CURLOPT_POST , true );
            curl_setopt ( $ch , CURLOPT_POSTFIELDS , $qstring );
        }
        curl_setopt ( $ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt ( $ch , CURLOPT_SSL_VERIFYPEER , false );
        curl_setopt ( $ch , CURLOPT_SSL_VERIFYHOST , false );

        $header = [ "content-type: application/x-www-form-urlencoded; charset=UTF-8" ];
        curl_setopt ( $ch , CURLOPT_HTTPHEADER , $header );
        $data = curl_exec ( $ch );

        $err_code = 0;
        if ( curl_errno ( $ch ) != 0 ) {
            $err_code = 9;
            $err_msg  = '接口通知失败:网络错误.' . curl_error ( $ch );
        }
        curl_close ( $ch );
        if ( $err_code ) {
            return false;
        } else {
            return $data;
        }
    }

    /**
     * 制作接口的请求地址
     *
     * @return string
     */
    public function create_url( $paras , $urlencode = false )
    {
        $url = '';
        if ( $urlencode ) {
            $url = http_build_query ( $paras );
        } else {
            foreach ( $paras as $key => $val ) {
                if ( $key == 'signature' ) {
                    $url .= $key . '=' . base64_encode ( $val ) . '&';
                } else {
                    $url .= $key . '=' . $val . '&';
                }
            }
            $url = substr ( $url , 0 , strlen ( $url ) - 1 );
        }
        return $url;
    }

    /**
     * 计算签名
     */
    public function _hmac_encode( $paras , $key )
    {
        if ( empty( $paras ) ) {
            return false;
        }
        if ( empty( $key ) ) {
            return false;
        }
        $qdata = $url = $sign = '';
        foreach ( $paras AS $_key => $_val ) {
            if ( $_key == 'signature' ) {
                $url .= $_key . '=' . base64_encode ( $_val ) . '&';
            } else {
                $url .= $_key . '=' . $_val . '&';
            }
        }
        $qdata = substr ( $url , 0 , -1 ) . $key;
        //echo 'orign_data   '.$qdata.'<br/>';
        //exit;
        $sign = md5 ( $qdata );
        return $sign;
    }

}
