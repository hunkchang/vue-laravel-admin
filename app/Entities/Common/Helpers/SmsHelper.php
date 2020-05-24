<?php

namespace App\Entities\Common\Helpers;

use Carbon\Carbon;
use Curder\AliyunCore\Exception\ClientException;
use Curder\AliyunCore\Exception\ServerException;
use Curder\LaravelAliyunSms\AliyunSms;
use Models\Common\Tables\Logs\SendSmsLog;
use Models\Enum\Aliyun\SmsCode;
use Models\Enum\Aliyun\SmsMessage;
use Models\Enum\Aliyun\SmsTemplate;

class SmsHelper
{
    /**
     * 发送阿里云短信
     * @param        $mobile
     * @param        $memberId
     * @param        $type
     * @param string $templateId
     * @return bool
     */
    public static function sendVerifyCode( $mobile , $memberId , $type = SendSmsLog::TYPE_REGISTER , $templateId = SmsTemplate::PUBLIC_SMS_TEMPLATE_ID )
    {
        $resultMsg = [ 'status' => SmsCode::SEND_OK , 'message' => SmsMessage::SEND_OK ];
        //一分钟之内只能发送一次
        $smsLog = SendSmsLog::where ( [ 'member_id' => $memberId , 'status' => 1 ] )->orderBy ( SendSmsLog::getInstance ()->getKeyName () , 'desc' )->first ();
        if ( !empty( $smsLog ) && ( Carbon::now ()->diffInSeconds ( $smsLog->created_at ) < 60 ) ) {

            $resultMsg[ 'status' ]  = SmsCode::SEND_ERROR;
            $resultMsg[ 'message' ] = SmsMessage::SEND_ONE_MINUTES_LIMITED;
            return $resultMsg;
        }

        /** @var AliyunSms $smsService */
        $smsService = app ( AliyunSms::class );

        $code   = \helper::randStr ( 1 , 6 );
        $params = [
            'code'    => $code ,
            'product' => 'Dysmsapi' ,
        ];


        try {
            $result = $smsService->send ( $mobile , $templateId , $params );
            switch ( $result->Code ) {
                case 'OK':
                    $ip                    = \Request::getClientIp ();
                    $sendSmsLog            = new SendSmsLog();
                    $sendSmsLog->member_id = $memberId;
                    $sendSmsLog->mobile    = $mobile;
                    $sendSmsLog->code      = $code;
                    $sendSmsLog->result    = json_encode ( $result );
                    $sendSmsLog->type      = $type;
                    $sendSmsLog->ip        = !empty( $ip ) ? $ip : '';

                    $sendSmsLog->save ();

                    break;
                case 'isv.BUSINESS_LIMIT_CONTROL':
                    $resultMsg[ 'status' ]  = SmsCode::SEND_LIMITED;
                    $resultMsg[ 'message' ] = SmsMessage::SEND_LIMITED;
                    break;
                default:
                    $resultMsg[ 'status' ]  = SmsCode::SEND_ERROR;
                    $resultMsg[ 'message' ] = SmsMessage::SEND_ERROR;
            }

        } catch ( ClientException $exception ) {

            $resultMsg[ 'status' ]  = SmsCode::SEND_ERROR;
            $resultMsg[ 'message' ] = SmsMessage::SEND_ERROR;
        } catch ( ServerException $exception ) {

            $resultMsg[ 'status' ]  = SmsCode::SEND_ERROR;
            $resultMsg[ 'message' ] = SmsMessage::SEND_ERROR;
        } catch ( \Exception $exception ) {

            \Log::error ( '短信发送失败: ' . $exception->getMessage () );
            $resultMsg[ 'status' ]  = SmsCode::SEND_ERROR;
            $resultMsg[ 'message' ] = SmsMessage::SEND_ERROR;
        }

        \Log::info ( print_r ( $resultMsg , true ) );
        return $resultMsg;
    }

    public static function verifyCode( $memberId , $code )
    {
        $resultMsg = [ 'status' => SmsCode::VERIFY_OK , 'data' => null , 'message' => SmsMessage::VERIFY_OK ];
        $smsLog    = SendSmsLog::where ( [ 'member_id' => $memberId , 'status' => 1 ] )->orderBy ( SendSmsLog::getInstance ()->getKeyName () , 'desc' )->first ();
        if ( empty( $smsLog ) ) {
            $resultMsg = [ 'status' => SmsCode::VERIFY_ERROR , 'data' => null , 'message' => SmsMessage::VERIFY_ERROR ];
            return $resultMsg;
        }

        $diffMinutes = Carbon::now ()->diffInSeconds ( $smsLog->created_at );
        //验证是否超时
        if ( $diffMinutes > 10 * 60 ) {
            $resultMsg = [ 'status' => SmsCode::VERIFY_OVERTIME , 'data' => null , 'message' => SmsMessage::VERIFY_OVERTIME ];
            return $resultMsg;
        }

        //验证验证码是否正确
        if ( $smsLog->code != $code ) {
            $resultMsg = [ 'status' => SmsCode::VERIFY_ERROR , 'data' => null , 'message' => SmsMessage::VERIFY_ERROR ];
            return $resultMsg;
        }

        $resultMsg[ 'data' ] = $smsLog;

        return $resultMsg;
    }

    /**
     * @param SendSmsLog $smsLog
     */
    public static function loseCode( $smsLog )
    {
        if ( is_null ( $smsLog ) ) {
            return;
        }
        $smsLog->status = 0;
        $smsLog->save ();
    }

    public static function sendSMS( $mobile , $content )
    {
        $uid     = config ( 'sms.web_sms_uid' );
        $pass    = config ( 'sms.web_sms_pass' );
        $config  = [ 'app_key' => $uid , 'app_secret' => $pass ];
        $obj_sms = new Webcsms( $config );
        $content = $content . '【' . config ( 'app.name' ) . '】';
        return $obj_sms->send ( $mobile , $content );

    }

    public static function sendJuheSms( $mobile , $code )
    {

    }
}
