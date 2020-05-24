<?php

return [
    'name'                => 'Admin' ,
    'middleware'          => [
        Modules\Admin\Http\Middleware\AdminAuthMiddleware::class,
        Modules\Admin\Http\Middleware\OperationLogMiddleware::class
        ] , // 加载中间件
    'ignoreMiddleware'    => 'Login/Login,Index/Index' ,
    'csrf_except'         => [
        '*upload' ,
        '*ckeditor_upload' ,
        'thematic*' ,
        'picture*' ,
        'subject*' ,
        'login/vcaptcha' ,
        'login/send_code' ,
    ] ,
//    'adminUserClass'=> \Models\Admin\Admin\AdminUser::class,
//    'api_domain'=>env('API_DOMAIN','')
];
