# mqtt

php-client-mqtt

## 调试模式

* 可以使用debug属性开启调试模式

```
$mqtt->debug = true;
```

* 开启debug模式后，默认会在runtime文件下写入日志，如果想改变日志存储位置，可以在实例化mqtt类时，将第四个参数传入要写入文件的路径及文件名。

## 发布

```
require_once __DIR__ .'/shared.php';
use Waljqiang\Mqtt\Mqtt;

try{
    $mqtt = new Mqtt($config['parameters'],$config['options']);
    if(!$mqtt->connect($config['clientid']))
        exit(-1);
    $rs = $mqtt->publish('abc','111');
    $mqtt->close();
    var_dump($mqtt);
    var_dump($rs);
}catch(\Exception $e){
    var_dump($e);
}
```

## 订阅

require_once __DIR__ .'/shared.php';
use Waljqiang\Mqtt\Mqtt;

try{
    $mqtt = new Mqtt($config['parameters'],$config['options']);
    if(!$mqtt->connect($config['clientid']))
        exit(-1);
    $mqtt->subscribe(['abc' => ['qos'=>0,'function' => 'procmsg']]);
    while($mqtt->proc()){

    }
    $mqtt->close();
}catch(\Exception $e){
    var_dump($e);
}

function procmsg($topic,$msg){
    var_dump($topic);
    var_dump($msg);
}

**备注**：

* subscribe第一个参数说明,abc为订阅的topic，qos为发送质量，function为接收消息回调；可以批量订阅多个topic

* qos质量说明

|值|说明|
|:---:|:---:|
|0|最多一次|
|1| 至少一次 |
|2|只有一次|

* function支持闭包形式，如下示例

    ```
    require_once __DIR__ .'/shared.php';
    use Waljqiang\Mqtt\Mqtt;

    try{
        $mqtt = new Mqtt($config['parameters'],$config['options']);
        if(!$mqtt->connect($config['clientid']))
            exit(-1);
        $mqtt->subscribe(['abc' => ['qos'=>0,'function' => function($topic,$msg){
                var_dump($topic);
                var_dump($msg);
            }]]);
        while($mqtt->proc()){

        }
        $mqtt->close();
    }catch(\Exception $e){
        var_dump($e);
    }
    ```

* 上面实例只能在cli模式下输出，如果要在浏览器中看结果，可以使用写文件的形式查看

    ```
    file_put_contents("./procmsg.txt",var_export(['topic'=>$topic,'msg'=>$msg],true),FILE_APPEND);
    ```