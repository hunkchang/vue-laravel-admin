<?php

namespace App\Console\Commands\Admin;

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
        AdminUsers::create(
            [
                'username' => 'admin',
                'password' => \Hash::make('admin'),
                'api_token' => null,
                'email'=>'admin@qq.com'
            ]
        );

        $this->info('密码生成成功');
    }
}
