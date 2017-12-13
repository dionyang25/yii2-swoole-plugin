# yii2-swoole-plugin

一个用于在swoole上运行yii2项目的composer包，类似的其实是有的，自己写了一个简单版的。

## 如何启动

1. 配置console设置（config/console.php），在component中添加组件
```php
    'yii2Swoole'=>[
        'class'=>'app\components\yii2Swoole\src\Server'
    ]
```

2. 准备入口文件，该入口文件主要是设置YII的启动参数，最终返回一个合并的启动数组配置即可
```php
defined('YII_DEBUG') or define('YII_DEBUG',true);
defined('YII_ENV') or define('YII_ENV', 'dev');
require(__DIR__ . '/../vendor/autoload.php');
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

4. 控制台使用如下命令启动

```php
./yii hello //启动
./yii hello/stop //终止
```