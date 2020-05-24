<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_users', function (Blueprint $table) {

            $table->increments('admin_user_id')->unsigned()->comment('用户ID');
            $table->string('username', 150)->unique()->comment('用户名');
            $table->string('email', 200)->comment('邮箱');
            $table->string('password', 60)->comment('密码');
            $table->string('api_token',80)->nullable()->comment('API TOKEN');
            $table->string('name', '100')->default('')->comment('名字或者昵称');
            $table->string('avatar')->nullable()->comment('头像');
            $table->rememberToken()->comment('前端TOKEN');
            $table->json('permissions')->default(new Expression('(JSON_ARRAY())'))->comment('权限');
            $table->string('email_verify_code',150)->nullable()->comment('邮箱验证码');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证时间');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_roles', function (Blueprint $table) {

            $table->increments('admin_role_id')->unsigned()->comment('后台用户组ID');
            $table->string('name')->comment('用户组名称');
            $table->integer('web_menu_id')->default(0)->comment('管理后台模块');
            $table->json('permissions')->default(new Expression('(JSON_ARRAY())'))->comment('权限');
            $table->string('desc',200)->comment('描述');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_menus', function (Blueprint $table) {

            $table->increments('web_menu_id')->unsigned()->comment('菜单ID')->comment('菜单ID');
            $table->string('module', 50)->default('admin')->comment('管理后台模块');
            $table->string('url', 150)->default('')->comment('网址');
            $table->string('class', 100)->comment('控制器');
            $table->json('methods',)->default(new Expression('(JSON_ARRAY())'))->comment('方法、操作');
            $table->integer('parent_menu_id')->default(0)->comment('父级ID');
            $table->string('icon', 100)->default('')->comment('图标');
            $table->integer('list_order')->default(99)->comment('排序');

            $table->timestamps();

        });


        Schema::create('admin_methods',function (Blueprint $table){

            $table->increments('method_id')->unsigned()->comment('Function ID');
            $table->string('name',100)->comment('名称');
            $table->string('method',100)->comment('方法');
            $table->string('desc')->comment('描述');

            $table->timestamps();
        });

        Schema::create('admin_web_modules',function (Blueprint $table){

            $table->increments('web_module_id')->unsigned()->comment('模块ID');
            $table->string('name',100)->comment('模块名称');
            $table->string('module_key',100)->comment('模块标识');
            $table->string('domain',200)->comment('域名');

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_users');
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('admin_menus');
        Schema::dropIfExists('admin_methods');
        Schema::dropIfExists('admin_web_modules');
    }
}
