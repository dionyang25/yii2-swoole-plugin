# yii2-swoole-plugin

一个用于在swoole上运行yii2项目的composer包，类似的其实是有的，自己写了一个简单版的。

## 如何启动

1. 配置console设置（config/console.php），在component中添加组件
```php
    'yii2Swoole'=>[
        'class'=>'app\components\yii2Swoole\src\Server'
    ]
```

2. 添加console启动控制器
```php

namespace app\commands;
use yii\console\Controller;
use Yii;
/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
        $server->entrance_file = __DIR__.'/../php-backstreet-api/index.php';
        $server->run();
    }

    public function actionStop()
    {
        Yii::$app->yii2Swoole->appStop();
    }
}

```