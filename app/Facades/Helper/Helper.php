<?php

namespace App\Facades\Helper;


use App\Library\Common\Helpers\AliOssHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Log;
use Validator;

/**
 *
 * @author zhangdawei
 * @web    http://www.bitzhu.com
 * @time 16:37
 */
class Helper
{

    /**
     * 上传图片到OSS
     * @param  string $targetFileName 目标文件
     * @param string $fileContents 文件内容
     * @param         $rootDir
     * @return bool
     */
    public static function putFileByContents($targetFileName, $fileContents = '', $rootDir = 'images')
    {
        //如果不需要远程图片
        if (config('filesystems.disks.local.active')) {
            return true;
        }

        try {
            $absoluteLocalFile = self::getAbsolutePath($targetFileName);
            if (!file_exists($absoluteLocalFile)) {
                $fileLocalPath = dirname($absoluteLocalFile);
                if (self::makeDir($fileLocalPath, 0775)) {
                    \Storage::put($rootDir . '/' . $targetFileName, $fileContents);
                }
            }
            $response = AliOssHelper::putFile($targetFileName, $absoluteLocalFile);
        } catch (Exception $e) {
            Log::alert('云存储上传文件异常：' . $e->getMessage());
            $response = false;
        }

        return $response;
    }

    /**
     * 上传图片到OSS
     * @param $targetFileName //上传后文件名字
     ** @param $absolutePath //文件的绝对路径
     * @return bool
     */
    public static function putFile($targetFileName, $absolutePath)
    {
        //如果不需要远程图片
        if (config('filesystems.disks.local.active')) {
            return true;
        }

        try {
            $response = AliOssHelper::putFile($targetFileName, $absolutePath);
        } catch (Exception $e) {
            Log::alert('上传七牛文件异常：' . $e->getMessage());
            $response = false;
        }
        return $response;
    }

    /**
     * 上传图片到七牛
     * @param $absolutePath //文件的绝对路径
     * @param $targetFileName //上传后文件名字
     * @return bool
     */
    public static function downLoadFile($absolutePath, $targetFileName)
    {
        try {
            //读取文件内容
            $contents = self::qiniuSingleton()->get($targetFileName);
            $response = file_put_contents($absolutePath, $contents);
        } catch (Exception $e) {
            Log::alert('上传七牛文件异常：' . $e->getMessage());
            $response = false;
        }
        return $response;
    }


    /**
     * 获取访问图片地址
     * @param        $key //图片相对路径
     * @param string $fops //参数 示例 'image/resize,w_1000'
     * @return mixed //返回参数
     */
    public static function getImagePreviewUrl($key, $fops = '')
    {
        $parseUrl = parse_url($key);
        if (isset($parseUrl['scheme'])) {
            return $key;
        }
        /**
         * 如果是本地
         */
        $url = $key;
        if (!config('filesystems.disks.local.active')) {
            $url = self::getStaticDomain() . '/' . $url;
        }
        if (!empty($fops)) {
            $url = $url . '?x-oss-process=' . $fops;
        }
        return $url;
    }

    /**
     * 删除七牛的图片
     * @param $key //七牛的文件路径
     * @return bool //返回成功与否状态
     */
    public static function deleteFile($key)
    {
        //判断文件是否存在
        if (!self::qiniuSingleton()->exists($key)) {
            Log::alert('文件不存在，无法删除：' . $key);
            return true;
        }
        return self::qiniuSingleton()->delete($key);
    }

    /**
     * 获取文件的物理路径
     * @param        $key
     * @param string $path
     * @return string
     */
    public static function getAbsolutePath($key, $path = '')
    {
        if (empty($path)) {
            $path = config('filesystems.disks.local.images_oot');
        }
        return $path . '/' . $key;
    }

    /**
     * 获取文件的相对路径
     * @param $key
     * @return mixed
     */
    public static function getRelativePath($key)
    {
        $root_path = self::getAbsoluteStaticPath();
        $uri = str_replace($root_path . '/', '', $key);
        return $uri;
    }

    /**
     * 获取文件的相对路径
     * @param $contentsText
     * @param $fpos
     * @return mixed
     */
    public static function __formatGetRichText($contentsText, $fpos = '')
    {
        $imgList = \phpQuery::newDocumentHTML($contentsText)->find('img');
        if ($imgList->length < 1) {
            return $contentsText;
        }
        $beforeSrcArr = [];
        $afterSrcArr = [];

        foreach ($imgList as $img) {
            $src = pq($img)->attr('src');
            array_push($beforeSrcArr, $src);
            $fullUrl = \helper::getImagePreviewUrl($src, $fpos);
            array_push($afterSrcArr, $fullUrl);
        }

        $contentsText = str_replace($beforeSrcArr, $afterSrcArr, $contentsText);
        return $contentsText;
    }

    /**
     * 格式化提交的富文本数据,把图片转换成指定的路径
     * @param string $contentsText
     * @return mixed|string
     */
    public static function __formatPostRichText($contentsText = '')
    {
        $imgList = \phpQuery::newDocumentHTML($contentsText)->find('img');
        if ($imgList->length < 1) {
            return $contentsText;
        }
        $beforeSrcArr = [];
        $afterSrcArr = [];
        foreach ($imgList as $img) {
            $src = pq($img)->attr('src');
            array_push($beforeSrcArr, $src);
            $afterSrc = \App\Library\Common\Helpers\Helper::getRelativeUrl($src);
            array_push($afterSrcArr, $afterSrc);
        }

        $contentsText = str_replace($beforeSrcArr, $afterSrcArr, $contentsText);
        return $contentsText;
    }

    public static function getStaticDomain()
    {
        $domains = config('filesystems.disks.local.static_domain');
        if (is_array($domains)) {
            return $domains[array_rand($domains)];
        }
        return $domains;
    }

    /**
     * @param $url
     * @return mixed
     */
    public static function getRelativeUrl($url)
    {
        $parseArr = parse_url($url);
        if (empty($parseArr['scheme'])) {
            return $url;
        }
        $path = $parseArr['path'];
        if (empty($path)) {
            return '';
        }
        return substr($path, 1);
    }

    /**
     * 获取上传文件的路径
     * @return string
     */
    public static function getFilePath()
    {

        $a = date('y');
        $b = date('m');
        $c = date('d');
        $path = $a . '/' . $b . $c . '/';
        // 分栏目
        return $path;
    }

    /**
     * 获取上传文件名
     * @return string
     */
    public static function getFileName()
    {
        list($usec) = explode(' ', microtime());
        $usec = substr($usec, 1, 5);
        $file_name = date('Hi') . md5(time() . $usec) . rand(10, 99);
        return $file_name;
    }

    /**
     * 创建目录树
     * @param string $folder 目录
     * @param int $mode 创建模式
     * @return bool
     */
    public static function makeDir($folder, $mode = 0775)
    {
        $old = umask(0);
        if (is_dir($folder) || @mkdir($folder, $mode, true)) {
            umask($old);
            return true;
        }
        if (!self::makeDir(dirname($folder), $mode)) {
            return false;
        }
        $return = @mkdir($folder, $mode, true);
        umask($old);
        return $return;
    }

    /**
     *
     *
     * /**
     * 获取静态的物理路径
     * @return string
     * @internal param $key
     */
    public static function getAbsoluteStaticPath()
    {
        return config('filesystems.disks.local.images_oot');
    }


    /**
     * 获取文件HASH值
     * @param $key //图片绝对路径
     * @return string
     */
    public static function getImageHash($key)
    {
        static $hashObject;
        if (empty($hashObject)) {
            $hashObject = new Imghash();
        }
        try {
            return $hashObject->getHashValue($key);
        } catch (Exception $e) {
            Log::alert('图片HASH生成失败:' . $key . ' - ' . $e->getMessage());
            return '';
        }
    }

    /**
     * 生成静态URL地址
     * @param string $to
     * @param array $params
     * @param bool $isSeo
     * @param bool $isDomain
     * @return string
     */
    public static function url($to = '', $params = [], $isSeo = false)
    {
        $paramGenerate = '';
        $uri = $to;
        if (empty(trim($uri))) {
            return '#';
        }
        if ($isSeo) {
            //拼凑参数
            if (!empty($params)) {
                foreach ($params as $key => $param) {
                    if (!is_null($param)) {
                        $paramGenerate[] = $key . '/' . $param;
                    }
                }
            }

            if (!empty($paramGenerate)) {
                $paramStr = implode('/', $paramGenerate);
                $uri = $uri . '/' . $paramStr;
            }
            return url($uri);
        } else {
            $paramArr = [];
            foreach ($params as $key => $param) {
                if (is_null($param)) {
                    continue;
                }
                if (is_array($param)) {
                    foreach ($param as $pkey => $pvalue) {
                        $paramArr[] = "{$key}[]=" . $pvalue;
                    }
                } else {
                    $paramArr[] = $key . '=' . $param;
                }
            }
            $paramStr = '';
            if (!empty($paramArr)) {
                $paramStr = '?' . implode('&', $paramArr);
            }
        }
        $isSsl = config('app.is_ssl');
        if (!$isSsl){
            $isSsl = \Request::isSecure();
        }
        return url($to, [], $isSsl) . $paramStr;
    }

    public static function getWwwUrl($path = '', $params = [], $isSeo = false)
    {
        $currentUrl = config('app.url');
        $currentUrl = str_replace(MODULE . '.', 'www.', $currentUrl);
        $url = $currentUrl . '/' . $path;
        return self::url($url, $params, $isSeo);
    }

    /**
     * 调试参数中的变量并中断程序的执行，参数可以为任意多个,类型任意，
     * 如果参数中含有'debug'参数，刚显示所有的调用过程。
     *
     * <code>
     * debug($var1, $obj1, $array1[,]...);
     * debug($var1, 'debug');
     * </code>
     */
    public static function debug()
    {
        $args = func_get_args();
        header('Content-type: text/html; charset=utf-8');
        echo "<pre>\n---------------------------------调试信息---------------------------------\n";
        foreach ($args as $value) {
            if (is_null($value)) {
                echo '[is_null]';
            } elseif (is_bool($value) || empty($value)) {
                var_dump($value);
            } else {
                print_r($value);
            }
            echo "\n";
        }
        $trace = debug_backtrace();
        $next = array_merge(
            [
                'line' => '??',
                'file' => '[internal]',
                'class' => null,
                'function' => '[main]',
            ], $trace[0]
        );

        /* if(strpos($next['file'], ZEQII_PATH) === 0){
          $next['file'] = str_replace(ZEQII_PATH, DS . 'library' . DS, $next['file']);
          }elseif (strpos($next['file'], ROOT_PATH) === 0){
          $next['file'] = str_replace(ROOT_PATH, DS . 'public' . DS, $next['file']);
          } */
        echo "\n---------------------------------输出位置---------------------------------\n\n";
        echo $next['file'] . "\t第" . $next['line'] . "行.\n";
        if (in_array('debug', $args)) {
            echo "\n<pre>";
            echo "\n---------------------------------跟踪信息---------------------------------\n";
            print_r($trace);
        }
        echo "\n---------------------------------调试结束---------------------------------\n";
        exit();
    }

    /**
     * 二维数组排序
     * @param     $multi_array //array
     * @param     $sort_key //array
     * @param int $sort
     * @param int $sort_type = SORT_REGULAR //将项目按照通常方法比较, SORT_NUMERIC - 将项目按照数值比较, SORT_STRING - 将项目按照字符串比较
     * @return bool
     */
    public static function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC, $sort_type = SORT_REGULAR)
    {
        if (is_array($multi_array)) {
            foreach ($multi_array as $row_array) {
                if (is_array($row_array)) {
                    $key_array[] = (is_numeric($row_array[$sort_key])) ? $row_array[$sort_key] : strtolower($row_array[$sort_key]);
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_array, $sort, $sort_type, $multi_array);
        return $multi_array;
    }

    /**
     * 获取客户端操作系统类型
     * @param string $AGENT
     * @return string
     */
    public static function get_client_os($AGENT = '')
    {
        if (empty($AGENT)) {
            $AGENT = $_SERVER["HTTP_USER_AGENT"];
        }
        if (strpos($AGENT, "Windows NT 5.0"))
            $os = "Windows 2000";
        elseif (strpos($AGENT, "Windows NT 5.1"))
            $os = "Windows XP";
        elseif (strpos($AGENT, "Windows NT 5.2"))
            $os = "Windows 2003";
        elseif (strpos($AGENT, "Windows NT 6.0"))
            $os = "Windows Vista";
        elseif (strpos($AGENT, "Windows NT 6.1"))
            $os = "Windows 7";
        elseif (strpos($AGENT, "Windows NT 6.2"))
            $os = "Windows 8";
        elseif (strpos($AGENT, "Windows NT"))
            $os = "Windows NT";
        elseif (strpos($AGENT, "Windows CE"))
            $os = "Windows CE";
        elseif (strpos($AGENT, "ME"))
            $os = "Windows ME";
        elseif (strpos($AGENT, "Windows 9"))
            $os = "Windows 98";
        elseif (strpos($AGENT, "unix"))
            $os = "Unix";
        elseif (strpos($AGENT, "linux"))
            $os = "Linux";
        elseif (strpos($AGENT, "SunOS"))
            $os = "SunOS";
        elseif (strpos($AGENT, "OpenBSD"))
            $os = "OpenBSD";
        elseif (strpos($AGENT, "FreeBSD"))
            $os = "FreeBSD";
        elseif (strpos($AGENT, "AIX"))
            $os = "AIX";
        elseif (strpos($AGENT, "Mac"))
            $os = "Mac";
        else
            $os = "Other";
        return $os;
    }

    /**
     * 字符串加密
     * @param string $string //需加密的字符
     * @param string $operation //加密或解密
     * @param string $key //网站加密key，防止破解
     * @param int $expiry
     * @return string
     */
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;
        $key = md5($key ? $key : config('app.encrypt_key'));
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(base64_decode(substr($string, $ckey_length))) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = [];
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode(base64_encode($result)));
        }
    }

    /**
     * 加密
     * @param string $data
     * @param string $key
     * @return string
     */
    function encrypt($data, $key = '')
    {
        $key = md5($key ? $key : config('app.key'));
        $prep_code = serialize($data);
        $block = mcrypt_get_block_size('des');
        if (($pad = $block - (strlen($prep_code) % $block)) < $block) {
            $prep_code .= str_repeat(chr($pad), $pad);
        }
        $encrypt = mcrypt_encrypt(MCRYPT_DES, $key, $prep_code, MCRYPT_MODE_ECB);
        return base64_encode(base64_encode($encrypt));
    }

    /**
     * 解密
     * @param        $str
     * @param string $key
     * @return mixed|string
     */
    function decrypt($str, $key = '')
    {
        $key = md5($key ? $key : config('app.key'));
        $str = base64_decode(base64_decode($str));
        $str = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
        $block = mcrypt_get_block_size('des');
        $pad = ord($str[($len = strlen($str)) - 1]);
        if ($pad && $pad < $block && preg_match('/' . chr($pad) . '{' . $pad . '}$/', $str)) {
            $str = substr($str, 0, strlen($str) - $pad);
        }
        return unserialize($str);
    }

    /**
     * 返回ajax json字符串
     * @param int $status 状态
     * @param string $msg 消息
     * @param int $ids 回调的ID
     * @param string $redirectUrl 回调的ID
     * @return string 返回结果
     * @internal param string $callaction 回调时间
     */
    public static function returnAjax($status = 0, $msg = '', $ids = 0, $redirectUrl = '')
    {
        if (!\Request::ajax() && !config('app.debug')) {
            return \Redirect::to('/');
        }
        $return = [];
        if (!empty($status)) {
            $return['status'] = $status;
        }

        if (!empty($callaction)) {
            $return['callaction'] = $callaction;
        }

        if (!empty($ids)) {
            $return['ids'] = $ids;
        }

        if (!empty($msg)) {
            $return['msg'] = $msg;
        }

        if (!empty($redirectUrl)) {
            $return['redirectUrl'] = $redirectUrl;
        }

        return self::returnJson($return);
    }

    public static function returnApi($status, $data = [], $messages = '')
    {
        if (empty($messages)) {
            $messages = isset(ApiCode::$codeMessage[$status]) ? ApiCode::$codeMessage[$status] : '';
        }
        return self::returnJson(['code' => $status, 'data' => $data, 'msg' => $messages]);
    }

    /**
     * 返回一个未转译的json请求
     * @param $reJson ['status'=>200,'remsg'=>'添加成功','redirect'=>'http://www.redirect.com'],redirectUrl可选
     * @return mixed
     */
    public static function returnJson($reJson)
    {
        return \Response::json($reJson, 200, ['Content-Type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    public static function get_client_ip($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = null;
        if ($ip !== null) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }

    /**
     * 生成付款编号
     * */
    public static function getOrderTradeNno()
    {

        $order_sn = 'JT-' . date('YmdHi') . '-' . str_pad(mt_rand(1, 1000), 5, '0', STR_PAD_LEFT);

        return $order_sn;
    }

    /**
     * @param $begin
     * @param $memberId
     * @return string
     */
    public static function getOrderNo($begin, $memberId)
    {
        $s = date('s');
        $last = ceil($s / 2);
        $orderNo = $begin . "-" . $memberId . '-' . date("ymdHi") . $last;

        return $orderNo;
    }

    /**
     * 获取当年的六合彩的生肖对应的号码
     * 配合 Plunar::solarToLunar(2015, 1, 28); 计算中国农历年
     * @param null $lunar_year 农历年份
     * @return array
     */
    public static function getMarkSixCodeList($lunar_year = null)
    {
        if (empty($lunar_year)) {
            return [];
        }

        $shengxiao_code = [
            ['1', '13', '25', '37', '49'],
            ['2', '14', '26', '38'],
            ['3', '15', '27', '39'],
            ['4', '16', '28', '40'],
            ['5', '17', '29', '41'],
            ['6', '18', '30', '42'],
            ['7', '19', '31', '43'],
            ['8', '20', '32', '44'],
            ['9', '21', '33', '45'],
            ['10', '22', '34', '46'],
            ['11', '23', '35', '47'],
            ['12', '24', '36', '48'],
        ];
        $shengxiao = ['shu', 'niu', 'hu', 'tu', 'long', 'she', 'ma', 'yang', 'hou', 'ji', 'gou', 'zhu'];
        $yshengxiao_index = ($lunar_year - 1900) % 12;
        $yshengxiao = $shengxiao[$yshengxiao_index];
        $reshengxiao = [];//重组生肖顺序
        foreach ($shengxiao as $k => $v) {
            if ($k >= $yshengxiao_index) {
                $reshengxiao[] = $v;
            }
        }
        $now_shengxiao = array_unique(array_merge($reshengxiao, $shengxiao));
        $mark_six_code_list = [];
        foreach ($now_shengxiao as $k => $v) {

            $mark_six_code_list[$v] = $shengxiao_code[$k == 0 ? 12 - 12 : 12 - $k];
        }

        return $mark_six_code_list;
    }

    public static function _show($status_code, $data = [])
    {
        $show_themplate = '';
        switch ($status_code) {
            case 404:
                $show_themplate = '404';//admin::notfound.index
                break;
            default:
                break;
        }
        if (\Request::ajax()) {
            return self::returnJson(['status' => $status_code, 'msg' => \View::make($show_themplate)->render()]);
        }
        return \View::make($show_themplate)->render();
    }

    public static function randStr($what, $length)
    {
        if ($what == 1) {
            $str = '0123456789';
        }

        if ($what == 2) {
            $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        if ($what == 3) {
            $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^7890-+-=';
        }
        $strlen = 62;
        while ($length > $strlen) {
            $str .= $str;
            $strlen += 62;
        }
        $str = str_shuffle($str);
        return substr($str, 0, $length);
    }

    public static function sendSystemError($subject, $data = [], $tpl = 'admin::mail.system_error')
    {

        try {
            \Mail::send($tpl, $data, function (\Illuminate\Mail\Message $message) use ($subject) {
                $toEmail = \config('mail.system_error_email');
                $to = $toEmail;
                return $message->to($to)->from(\config('mail.from.address'), $subject)->subject($subject);
            });
            return true;
        } catch (Exception $exception) {
            return false;
        }

    }

    public static function exceptionToLog(\Throwable $e, $isReturn = false)
    {
        $string = 'Message:' . $e->getMessage() . ' ';
        $string .= 'getFile:' . $e->getFile() . ' ';
        $string .= 'getLine:' . $e->getLine() . PHP_EOL;
        $string .= 'Trace:' . PHP_EOL;
        foreach ($e->getTrace() as $key => $trace) {
            $string .= '#' . $key . ' ';
            if (isset($trace['file'])) {
                $string .= 'File:' . $trace['file'] . ' ';
            }
            if (isset($trace['line'])) {
                $string .= 'Line:' . $trace['line'] . ' ';
            }
            if (isset($trace['function'])) {
                $string .= 'function:' . $trace['function'] . ' ';
            }
            if (isset($trace['class'])) {
                $string .= 'class:' . $trace['class'] . ' ';
            }
            $string .= PHP_EOL;
        }

        if ($isReturn) {
            return $string;
        }
        Log::error($string);
    }

    public static function formatNumber($number, $length)
    {
        return sprintf("%." . $length . "f", $number);
    }

    /**
     * 格式化下划线
     * @param $str
     * @return string
     */
    public static function formatUnderLine($str)
    {

        $actionArr = explode('_', $str);
        $actionArr = array_map('ucfirst', $actionArr);
        return implode('', $actionArr);
    }

    /**
     * 把单词大写转成下划线连接
     * @param $name
     * @return string
     */
    public static function formatUcFirstToUnderLine($name)
    {
        $temp_array = [];
        for ($i = 0; $i < strlen($name); $i++) {
            $ascii_code = ord($name[$i]);
            if ($ascii_code >= 65 && $ascii_code <= 90) {
                if ($i == 0) {
                    $temp_array[] = chr($ascii_code + 32);
                } else {
                    $temp_array[] = '_' . chr($ascii_code + 32);
                }
            } else {
                $temp_array[] = $name[$i];
            }
        }
        return implode('', $temp_array);
    }

    /**
     * 复制文件夹
     * @param $src
     * @param $dst
     */
    public static function copyDir($src, $dst)
    {

        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::copyDir($src . '/' . $file, $dst . '/' . $file);
                    continue;
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * 表单校验基类
     * @param        $inputs
     * @param        $rules
     * @param string $langPath
     * @param bool $ignoreAjax
     * @return bool|json
     */
    public static function validationForm($inputs, $rules, $langPath = '', $ignoreAjax = false)
    {

        $validator = Validator::make($inputs, $rules);
        //验证表单
        if ($validator->fails()) {
            $messages = $validator->errors()->getMessages();

            foreach ($messages as $mkey => $message) {
                //查询表单错误是否存在
                if (!$ignoreAjax) {
                    return \helper::returnAjax(404, implode('<br/\>', $message));
                }
                return implode('<br/\>', $message);
            }
        }

        //检验合格,没有错误
        return false;
    }

    /**
     * 加载后台CSS
     * @param $url
     * @return \Illuminate\Support\HtmlString|string
     */
    public static function adminPublicCss($url)
    {
        return \Html::style(self::adminPublicUrl($url));
    }

    /**
     * 加载后台JS
     * @param $url
     * @return \Illuminate\Support\HtmlString|string
     */
    public static function adminPublicScript($url)
    {
        return \Html::script(self::adminPublicUrl($url));
    }

    public static function adminPublicUrl($url)
    {
        $urlArr = parse_url($url);
        if (isset($urlArr['scheme'])) {
            return $url;
        } else {
            $url = config('app.admin_static_domain') . '/' . $url;
        }

        return $url;
    }

    /**
     * 加载后台JS
     * @param $url
     * @return \Illuminate\Support\HtmlString|string
     */
    public static function adminPublicImages($url)
    {
        return \Html::image(self::adminPublicUrl($url));
    }

    public static function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s', time());
    }

    /**
     * @param $group
     * @param $key
     * @return null
     * @throws Exception
     */
    public static function getConfig($group, $key = '')
    {
        static $configList;

        if (!isset($configList[$group])) {

            $cacheKey = 'config.' . $group;

            $configList[$group] = \Cache::rememberForever($cacheKey, function () use ($group) {
                $pluck = SettingParameter::keyValuePluck($group);
                if (empty($pluck)) {
                    return [];
                }
                return $pluck;
            });
        }

        if (empty($key) && isset($configList[$group])) {
            return $configList[$group];
        }

        if (isset($configList[$group][$group . '.' . $key])) {
            return $configList[$group][$group . '.' . $key];
        }

        return null;
    }

    /**
     * 刷新缓存数据
     * @param $group
     */
    public static function cacheConfig($group)
    {
        $cacheKey = 'config.' . $group;

        $configList = SettingParameter::keyValuePluck($group);
        if (empty($configList)) {
            return;
        }
        $configList = $configList->toArray();
        \Cache::forever($cacheKey, $configList);
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function returnApiData($data)
    {
        if (!empty($data)) {
            return self::returnApi(ApiCode::STATUS_OK, $data, ApiMsg::SUCCESS);
        } else {
            return self::returnApi(ApiCode::STATUS_ERROR, $data, ApiMsg::DATA_EMPTY);
        }
    }

    public static function formatAreaJson($jsonStr)
    {
        $areaArr = [];
        $areaObject = json_decode($jsonStr);
        if (isset($areaObject->province)) {
            array_push($areaArr, $areaObject->province);
        }
        if (isset($areaObject->city)) {
            array_push($areaArr, $areaObject->city);
        }
        if (isset($areaObject->district)) {
            array_push($areaArr, $areaObject->district);
        }
        if (isset($areaObject->street)) {
            array_push($areaArr, $areaObject->street);
        }
        $value = implode(' ', $areaArr);
        return $value;
    }

    /**
     * 通过HTTP获取网页内容
     * @param       $url
     * @param array $params
     * @param int $time
     * @return null|\Psr\Http\Message\ResponseInterface|string
     */
    public static function httpGetContents($url, $params = [], $time = 1)
    {
        static $curlClient;
        if (is_null($curlClient)) {
            $curlClient = new Client();
        }
        $result = null;
        try {
            if (!empty($params)) {
                $paramsUri = http_build_query($params);
                $url = $url . '?' . $paramsUri;
            }
            $result = $curlClient->get($url);
            return $result->getBody()->getContents();
        } catch (\Throwable $exception) {
            Log::alert($exception->getMessage());
            if ($time < 3) {
                return self::httpGetContents($url, $params, ++$time);
            }
            return '';
        }
    }

    /**
     * 通过HTTP获取网页内容
     * @param       $url
     * @param array $params
     * @param int $time
     * @return null|\Psr\Http\Message\ResponseInterface|string
     */
    public static function httpPostContents($url, $params = [], $time = 1)
    {
        static $curlClient;
        if (is_null($curlClient)) {
            $curlClient = new Client();
        }
        $result = null;
        try {
            $result = $curlClient->post($url, ['form_params' => $params]);
            if ($result->getStatusCode() == 200) {
                return $result->getBody()->getContents();
            } else {
                if ($time < 3) {
                    return self::httpPostContents($url, $params, ++$time);
                } else {
                    return '';
                }
            }
        } catch (ServerException $exception) {
            Log::alert($exception->getMessage());
            if ($time < 3) {
                return self::httpPostContents($url, $params, ++$time);
            }
            return '';
        }
    }

    /**
     * 求两个已知经纬度之间的距离,单位为米
     *
     * @param lng1 $ ,lng2 经度
     * @param lat1 $ ,lat2 纬度
     * @return float 距离，单位米
     * @author www.Alixixi.com
     */
    function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        // 将角度转为狐度
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    }

    /*
   * 	作用：生成签名
   */
    public static function getApiSign($Obj, $key)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        unset($Parameters['sign']);
        $String = self::formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $key;
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     *    作用：格式化参数，签名过程需要使用
     */
    public static function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    public static function validateAddress($address)
    {
    }

    public static  function curl_request($url,$post='',$cookie='', $returnCookie=0){


        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{
            return $data;
        }
    }

    /**
     * Hash the given value against the bcrypt algorithm.
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     */
    public static function bcrypt($value, $options = [])
    {
        return app('hash')->driver('bcrypt')->make($value, $options);
    }

    public static function response($data, $message = '', $code = 200, $headers = [])
    {
        return \Response::json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], 200, $headers);
    }

    public static function responseMessage($message = '', $code = 200)
    {
        return self::response([], $message, $code);
    }

    public static function responseError($message = '', $code = 400)
    {
        return self::response([], $message, $code);
    }

    /**
     * @param $url
     * @param bool $isVueRoute
     * @param string $message
     * @param string $type info/success/warning/error
     * @return \Illuminate\Http\JsonResponse
     */
    public static function responseRedirect($url, $isVueRoute = true, $message = null, $type = 'success')
    {
        return self::response([
            'url' => $url,
            'isVueRoute' => $isVueRoute,
            'type' => $type
        ], $message, 301);
    }

}

