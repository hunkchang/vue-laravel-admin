<?php
/**
 * 中奖赔率,操盘管理
 * Created by PhpStorm.
 * User: bowen
 * Date: 2017/1/11
 * Time: 21:39
 */

namespace Models\Common;



class OperationLog extends BaseModel
{
    protected $table = 'operation_log';

    public static function instance(){
        static $instance;
        if(empty($instance)){
            $instance = new self();
        }
        return $instance;
    }
    public function getLogList($where){
        $listdata = $this->select(array('admin_users.username as username','operation_log.content as content','operation_log.module_name as modulename',
            'operation_log.ip as ip','operation_log.opration_time as time'))
            ->leftJoin('admin_users','admin_users.id','=','operation_log.admin_id')
            ->where($where)
            ->orderBy('time','desc')
            ->paginate(15);
        return $listdata;
    }

    /**
     * 记录日志
     * @param $content
     * @param $module_name
     * @internal param $ip
     */
    public static function saveLog($content, $module_name)
    {
        $log = array();
        $log['content'] = $content;
        $log['module_name'] = $module_name;
        $log['admin_id'] = \Session::get('adminId');;
        $log['ip'] = \Request::ip();
        $log['request_info'] = CONTROLLER.' - '.ACTION;
        $log['opration_time'] = date('Y-m-d H:i:s');
        $log['opration_timestamp'] = time();
        self::insert($log);
    }

}