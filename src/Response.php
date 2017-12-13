<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/13
 * Time: 上午11:20
 */
namespace app\components\yii2Swoole\src;
class Response extends \yii\web\Response {

    public $responseBySwoole;

    protected function sendContent()
    {
        if ($this->stream === null) {
            $this->responseBySwoole->end($this->content);
            return;
        }

        set_time_limit(0); // Reset time limit for big files
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list ($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                $this->responseBySwoole->end(fread($handle, $chunkSize));
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                $this->responseBySwoole->end(fread($this->stream, $chunkSize));
                flush();
            }
            fclose($this->stream);
        }
    }

    /**
     * Sends the response headers to the client
     */
    protected function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }
        $headers = $this->getHeaders();
        foreach ($headers as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            // set replace for first occurrence of header but false afterwards to allow multiple
//            $replace = true;
            foreach ($values as $value) {
                $this->responseBySwoole->header($name,$value);
//                header("$name: $value", $replace);
//                $replace = false;
            }
        }
        $statusCode = $this->getStatusCode();
//        $this->responseBySwoole->header("HTTP/{$this->version} {$statusCode} {$this->statusText}");
        $this->sendCookies();
    }
}