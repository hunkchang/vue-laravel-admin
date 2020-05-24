<?php
/**
 * Created by PhpStorm.
 * User: 32823
 * Date: 2016/12/7
 * Time: 15:43
 */

namespace App\Dispatch;

use Input;
use Request;
use Route;
use URL;
use function GuzzleHttp\Psr7\build_query;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


class MyUrlRouteDispatch
{
    protected $controller; //执行的控制器
    protected $action; //执行的事件
    protected $routes;
    protected $groupRoute;
    protected $urlPath;
    protected $controllerNameSpace; //控制器的命名空间
    protected $fullControllerPath = 2;
    protected $controllerDepth;
    protected $mainUrl;

    private static $instantiate;

    public static function instantiate()
    {
        if ( is_null ( self::$instantiate ) ) {
            self::$instantiate = new self();
        }
        return self::$instantiate;
    }

    public function run()
    {
        if ( config ( 'app.is_https' ) && !Request::isSecure () ) {
            $url = config ( 'app.url' );
            $url = str_replace ( 'http://' , '' , $url );
            header ( 'Location:https://' . $url );
            exit;
        }
        $this->init ()->parseUrl ()->setDefined ()->setParams ()->setMiddleWare ()->setRun ();
    }

    /**
     * 初始化
     * @return $this
     */
    protected function init()
    {
        $this->controllerNameSpace = "\\Modules\\" . ucfirst ( MODULE ) . "\\Http\\Controllers\\";

        return $this;
    }

    /**
     * 获取控制器的深度
     * 设置控制器的路径
     */
    protected function setControllerPath()
    {
        //默认控制器
        $defaultController     = config ( MODULE . '.default_controller' ) ? config ( MODULE . '.default_controller' ) : 'Index';
        $this->controllerDepth = 3;
        if ( count ( $this->routes ) == 2 ) {
            //深度
            $this->controllerDepth = 2;
        }

        //完整路径
        $firstUrl  = isset( $this->routes[ 1 ] ) ? \helper::formatUnderLine ( $this->routes[ 1 ] ) : '';
        $secondUrl = isset( $this->routes[ 2 ] ) ? \helper::formatUnderLine ( $this->routes[ 2 ] ) : '';

        //如果路径分隔有三个
        if ( count ( $this->routes ) > 2 ) {
            $this->fullControllerPath = $this->controllerNameSpace . $firstUrl . '\\' . $secondUrl . "Controller";
            if ( !class_exists ( $this->fullControllerPath ) ) {
                $this->controllerDepth = 2;
            }
        }
        //主URL地址
        $this->mainUrl = '/';
        $mainUrlArr    = [];
        //两级路径
        if ( $this->controllerDepth == 3 ) {
            //获取控制器
            $this->controller = !empty( $secondUrl ) ? $secondUrl : $defaultController; //获取控制器
            //获取事件
            $this->action = ( isset( $this->routes[ 3 ] ) && !empty( $this->routes[ 3 ] ) ) ? \helper::formatUnderLine ( $this->routes[ 3 ] ) : 'Index'; //获取事件
        } else { //一级路经
            //获取控制器
            $this->controller = !empty( $firstUrl ) ? $firstUrl : $defaultController; //获取控制器
            //获取事件
            $this->action = !empty( $secondUrl ) ? $secondUrl : 'index'; //获取事件
            //设置完全的
            $this->fullControllerPath = $this->controllerNameSpace . $this->controller . "Controller";
        }

        for ( $i = 1 ; $i < $this->controllerDepth ; $i++ ) {
            if ( isset( $this->routes[ $i ] ) && !empty( $this->routes[ $i ] ) ) {
                array_push ( $mainUrlArr , $this->routes[ $i ] );
            }
        }
        //当前主URL
        $this->mainUrl = '/' . implode ( '/' , $mainUrlArr );
        return $this;
    }

    //解析URL
    protected function parseUrl()
    {
        //组织路由
        $uri = URL::getRequest ()->getRequestUri ();
        //解析URL地址
        $parseUrl = parse_url ( $uri );
        //路由匹配
        $this->routes = explode ( '/' , $parseUrl[ 'path' ] );
        //url地址
        $this->urlPath = $parseUrl[ 'path' ];
        //设置控制器路径
        $this->setControllerPath ();

        return $this;
    }

    //设置宏定义
    protected function setDefined()
    {
        //定义控制器
        if ( !empty( $this->controller ) ) {
            //定义当前访问位置
            //下划线的转换成驼峰式
            $cArr       = explode ( '_' , $this->controller );
            $cArr       = array_map ( 'ucfirst' , $cArr );
            $controller = implode ( '' , $cArr );
            //删除前面两个,因为已经没有用处了
            for ( $i = 0 ; $i < $this->controllerDepth ; $i++ ) {
                array_shift ( $this->routes );
            }
            //页面不存在
            if ( !class_exists ( $this->fullControllerPath ) ) {
                \Log::info( $this->fullControllerPath . ' not found. Server Info:' . print_r ( $_SERVER , true ) );
                if ( !config ( 'app.debug' ) ) {
                    $this->fullControllerPath = '\\Modules\\' . ucfirst ( MODULE ) . '\\Http\\Controllers\\NotFoundController';
                }
            }

        }

        if ( !empty( $this->controller ) ) {
            //如果事件命名是有两个单词的,支持下划线分隔,每个单词大写开头,驼峰式
            array_shift ( $this->routes );
            //设定事件
            $this->action = \helper::formatUnderLine ( $this->action );

            define ( 'CONTROLLER' , $this->controller );
            define ( 'ACTION' , $this->action );
            define ( 'MAIN_URL' , $this->mainUrl );
        }

        define ('CURRENT_ROUTE_NAME',MODULE.'.'.CONTROLLER.'.'.ACTION);

        return $this;
    }

    //设定参数
    protected function setParams()
    {
        //设定参数
        if ( !empty( $this->routes ) ) {

            $params = [];
            // 用奇数参数作key，用偶数作值
            foreach ( $this->routes as $number => $value ) {
                if ( $number & 1 ) {
                    $params[ $this->routes[ $number - 1 ] ] = urldecode ( $value );
                }
            }
            //合并参数
            Request::merge($params);
        }

        return $this;
    }

    /**
     * 设置中间件
     * @return $this
     */

    protected function checkIgnoreMiddleware($ignoreMiddleware){
       return defined ( 'MODULE' ) &&
        !in_array ( CONTROLLER , $ignoreMiddleware ) && //控制器不在忽略中
        !in_array ( CONTROLLER . '/' . ACTION  , $ignoreMiddleware );  //事件不在忽略中
    }

    protected function setMiddleWare()
    {
        //加载忽略中间件控制器的配置
        $groupRoute       = [];
        $ignoreMiddleware = explode ( ',' , config ( MODULE . '.ignoreMiddleware' ) );
        //加载忽略中间件的控制器的配置
        if ( $this->checkIgnoreMiddleware($ignoreMiddleware)) {
            //相关中间件核心设置请查看 app/Http/Kernel.php
            $middlewares = config ( MODULE . '.middleware' );
            if ( !empty( $middlewares ) ) {
                foreach ($middlewares as &$middleware ){
                    $middleware =  $middleware;
                }
                $groupRoute[ 'middleware' ] = $middlewares;
            }
        }
        //
        $this->groupRoute = $groupRoute;

        return $this;
    }

    //事件绑定模块
    protected function setRun()
    {
        /**
         * 绑定模块
         */
        Route::group ( $this->groupRoute , function () {
            $method = strtolower ( Request::method () );
            Route::$method( urldecode ( $this->urlPath ) , $this->fullControllerPath . '@' . $method . $this->action );
        } );
    }
}


