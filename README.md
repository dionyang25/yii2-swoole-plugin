# yii2-swoole-plugin

一个用于在swoole上运行yii2项目的composer包，类似的其实是有的，自己写了一个简单版的。

## composer包安装

composer require dionyang25/yii2-swoole-plugin

## composer包地址

https://packagist.org/packages/dionyang25/yii2-swoole-plugin

## 如何启动

1. 配置console设置（config/console.php），在component中添加组件
```php
    'yii2Swoole'=>[
        'class'=>'yii2Swoole\Server'
    ]
```

这里可以加入对swoole的配置，支持的配置如下（下图为默认配置）

```php
    'yii2Swoole'=>[
        'class'=>'app\components\yii2Swoole\src\Server',
        'config'=>[
              'daemonize'=>0,
              'reactor_num'=>4,
              'worker_num'=>20,
              'max_request' => 100,
              'pid_file'=> __DIR__ . '/../../../runtime/server.pid'
        ]
    ]
```

2. 准备入口文件，该入口文件主要是设置你的YII应用服务器的启动参数，最终返回一个合并的启动数组配置即可
```php
defined('YII_DEBUG') or define('YII_DEBUG',true);
defined('YII_ENV') or define('YII_ENV', 'dev');
$config = \yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../config/web.php') ,
    require(__DIR__ . '/../config/web-local.php')
);

return $config;
```

3. 添加console启动控制器
```php

namespace app\commands;
use yii\console\Controller;
use Yii;
/**
 * @author DionYang
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex()
    {
        $server =Yii::$app->yii2Swoole;
        $server->entrance_file = __DIR__.'/../php-backstreet-api/index-test.php';
        $server->run();
    }

    public function actionStop()
    {
        $this->stdout('Already Stop'.PHP_EOL);
        Yii::$app->yii2Swoole->appStop();
    }
}
```

4. 需要修改nginx配置如下，注意，如果在yii2中启用pathinfo模式，那么三个额外的header必须要添加，否则无法正确匹配路由。
示例如下：

```nginx
location ^~ /php-backstreet-api {
    root /Users/admin/www/php-backstreet-api;
  
    location ~ \.(css|js|jpg|png|gif)$ {
            root /Users/admin/www;
    }
    location ~ / {
        try_files $uri $uri/ /php-backstreet-api/index.php$is_args$args;
        #fastcgi_pass   127.0.0.1:9003;
        #fastcgi_index  index.php;
        #fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        #include        fastcgi_params;
            proxy_set_header request_uri $request_uri;
            proxy_set_header script_name $script_name;
            proxy_set_header script_filename $document_root$fastcgi_script_name;
            proxy_set_header Connection "keep-alive";
            proxy_set_header X-REAL-IP $remote_addr;
            proxy_pass http://127.0.0.1:9778;
    }
}
```


5. 控制台使用如下命令启动

```php
./yii hello //启动
./yii hello/stop //终止
```
