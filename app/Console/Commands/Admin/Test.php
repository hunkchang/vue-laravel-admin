<?php

namespace App\Console\Commands\Admin;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Modules\Admin\Entities\Admin\AdminUsers;
use Str;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Admin test';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $client = new Client();
        $request = $client->get('https://www.amazon.com/-/zh/gp/page/refresh?acAsin=B081QZ6FS2&asinList=B07LF3FYY9&auiAjax=1&parentAsin=B07C7Y3166&pgid=apparel_display_on_website&psc=1&triggerEvent=Twister',['proxy'=>'socks5://127.0.0.1:1086']);
        $responseText =  $request->getBody()->getContents();

        $responseArr = explode('&&&',$responseText);
        print_r($responseArr);
        /*AdminUsers::create(
            [
                'username' => 'admin',
                'password' => \Hash::make('admin'),
                'api_token' => null,
                'email'=>'admin@qq.com'
            ]
        );

        $this->info('密码生成成功');*/
    }
}
