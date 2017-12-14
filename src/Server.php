<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/12
 * Time: 下午3:02
 */
namespace yii2Swoole;
use yii\base\Component;
use swoole_http_server;
use yii\web\Application;

class Server extends Component {
    public $host = "127.0.0.1";
    public $port = "9778";
    public $mode = SWOOLE_PROCESS;
    public $socket_type = SWOOLE_TCP;
    public $config = [];
    public $entrance_file;//项目入口文件
    private $server;
    private $custom_config = [
        'daemonize'=>0,
        'reactor_num'=>4,
        'worker_num'=>20,
        'max_request' => 100,
        'pid_file'=> __DIR__ . '/../../../runtime/server.pid'

    ];
    public function init()
    {
        if(!$this->getPid()){
            $this->server = new swoole_http_server($this->host,$this->port,$this->mode,$this->socket_type);
            //设置配置
            $this->config = array_merge($this->custom_config,$this->config);
            $this->server->set($this->config);
            $this->server->on('request',[$this, 'Request']);
        }
        parent::init();
    }

    public function run(){
        $this->server->start();
    }

    public function Request($request,$response){
        $this->dealRequest($request);
        $this->appRun($request,$response);
    }

    /**
     * 处理请求 赋值给$_SERVER $_GET $_POST $_COOKIE $_FILE
     */
    private function dealRequest($request){
        $_GET = isset($request->get) ? $request->get : [];
        $_POST = isset($request->post) ?  $request->post : [];
        $_COOKIE = isset($request->cookie) ?  $request->cookie : [];
        if( isset($request->files) ) {
            $files = $request->files;
            foreach ($files as $k => $v) {
                if( isset($v['name']) ){
                    $_FILES = $files;
                    break;
                }
                foreach ($v as $key => $val) {
                    $_FILES[$k]['name'][$key] = $val['name'];
                    $_FILES[$k]['type'][$key] = $val['type'];
                    $_FILES[$k]['tmp_name'][$key] = $val['tmp_name'];
                    $_FILES[$k]['size'][$key] = $val['size'];
                    if(isset($val['error'])) $_FILES[$k]['error'][$key] = $val['error'];
                }
            }
        }
        if(isset($request->header['request_uri'])){
            $request->server['request_uri'] = $request->header['request_uri'];
        }
        if(isset($request->header['script_name'])){
            $request->server['script_name'] = $request->header['script_name'];
        }
        if(isset($request->header['script_filename'])){
            $request->server['script_filename'] = $request->header['script_filename'];
        }
        $server = isset($request->server) ? $request->server : [];
        $header = isset($request->header) ? $request->header : [];

        foreach ($server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
            unset($server[$key]);
        }
        foreach ($header as $key => $value) {
            $_SERVER['HTTP_'.strtoupper($key)] = $value;
        }
    }

    /**
     * 跑起应用程序
     * @param $request
     * @param $response
     */
    public function appRun($request,$response){
        $config = require($this->entrance_file);
        //装载自定义request response类
        $config['components']['request'] = [
            'class'=>Request::className(),
        ];
        $config['components']['response'] = [
            'class'=>Response::className(),
            'responseBySwoole'=>$response
        ];
        //启动app
        (new Application($config))->run();
    }

    public function appStop(){
        if($this->getPid()){
            $pid = file_get_contents($this->custom_config['pid_file']);
            if (posix_getpgid($pid)) {
                return posix_kill($pid,SIGTERM);
            }else{
                unlink($this->custom_config['pid_file']);
            }
        }else{
            return false;
        }
    }

    public function getPid(){
        if(file_exists($this->custom_config['pid_file'])){
            $pid = file_get_contents($this->custom_config['pid_file']);
            if (posix_getpgid($pid)) {
                return $pid;
            }
        }else{
            return false;
        }
    }
}