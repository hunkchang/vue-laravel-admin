前后端分离后台管理系统，使用Vue Element admin + Laravel 7.0构建。。
权限认证带后端Token/Session认证和前端vue.js的动态权限

---
## 开发步骤
### 配置
```bash
# 安装
composer install

# 复制配置文件
cp .env.example .env

# 生成加密key
php artisan key:generate

# 配置env中的数据库链接
配置数据库名称、用户名和密码

# 数据库迁移和填充
php artisan migrate
php artisan db:seed
php artisan admin:test

# 启动 (或者用普通方式启动laravel项目)
域名绑定到public/admin目录

yarn (或 npm install)
yarn run dev (或 npm run dev)
```
## 后端
### 技术栈
- Laravel 7.0
- Redis
- ...
## 前端
### 技术栈
- Vue全家桶
- Axios
- Laravel Mix
- ...

## License
The vue-Laravel-admin is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
