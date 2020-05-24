<?php

namespace Models\Common;

use Models\Admin\Admin\AdminUser;
use Models\Admin\Admin\WebModules;

/**
 * @property integer admin_id
 * @property integer agent_id
 * @property integer shop_id
 * @property integer web_module_id
 * @property string  controller
 * @property string  action
 * @property string  description
 * @property string  ip
 * Class DataLog
 * @package Models\Admin\Log
 */
class DataLogs extends BaseModel
{
    protected $table   = 'data_logs';
    protected $orderBy = [
        'created_at' => 'desc' ,
    ];

    const LOGIN_LOG_TYPE          = 1;
    const MEMBER_LOG_SETTING_TYPE = 2;
    const STATUS_LOG_TYPE         = 3;
    const CASH_LOG_TRANSFER_TYPE  = 4;

    /**
     * @param array     $input
     * @param AdminUser $adminUserInfo
     */
    public static function info( $input , $adminUserInfo )
    {
        self::unsetFields ( $input );

        $dataLog           = new self();
        $dataLog->admin_id = $adminUserInfo->getKey ();
        $dataLog->agent_id = (int) $adminUserInfo->agent_id;
        if ( isset( $adminUserInfo->shops_id ) ) {
            $dataLog->shop_id = $adminUserInfo->shops_id;
        }

        $modulePluck = WebModules::getInstance ()->getPluckId ();
        if ( isset( $modulePluck[ MODULE ] ) ) {
            $dataLog->web_module_id = $modulePluck[ MODULE ];
        }

        $dataLog->controller  = CONTROLLER;
        $dataLog->action      = ACTION;
        $dataLog->description = json_encode ( $input );
        $dataLog->ip          = \Request::getClientIp ();

        $dataLog->save ();
    }

    protected static function unsetFields( &$input )
    {
        $unsetList = [ '_token' , 'info' , 'http_referer' , 'is_ajax' , 'password' , 'pay_password','password_confirmation' ];
        foreach ( $unsetList as $field ) {
            if ( isset( $input[ $field ] ) ) {
                unset( $input[ $field ] );
            }
        }
    }
}