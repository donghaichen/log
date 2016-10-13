<?php
/**
 * Clover Log 类
 *
 * @author Donghaichen [<chendongahai888@gmail.com>]
 * @todo psr/log规范日志接口
 */

namespace Clovers\Log;
use BadMethodCallException;

class Log
{
    // 日志信息
    protected static $log = [];
    // 配置参数
    protected static $config = [];
    // 日志等级
    protected static $level = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
    // 日志写入驱动
    protected static $driver;

    // 当前日志授权key
    protected static $key;

    /**
     * 静态调用
     * @param $method
     * @param $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        if (in_array($method, self::$level)) {
            array_push($args, $method);
            if(count($args) == 3){
                self::$config['type'] = $args[0];
                unset($args[0]);
            }
            return call_user_func_array('\\Clovers\\Log\\Log::record', $args);
        }else{
            throw new BadMethodCallException('level not exists:' . $method, self::$level);
        }
    }

    /**
     * 日志初始化
     * @param array $config
     */
    public static function init($config = [])
    {
        $driver         = isset($config['driver']) ? $config['driver'] : 'File';
        $class        = false !== strpos($driver, '\\') ? $driver : '\\Clovers\\Log\\Driver\\' . ucwords($driver);
        self::$driver = $class;
        self::$config = $config;

        if (!class_exists($class)) {
            throw new BadMethodCallException('class not exists:' . $class, $class);
        }
    }


    /**
     * 记录调试信息
     * @param mixed  $msg  调试信息
     * @param string $type 信息类型
     * @return void
     */
    public static function record($msg, $type = 'log')
    {
        self::$log[$type] = $msg;
        self::write();
    }

    /**
     * 实时写入日志信息
     * @return bool
     */
    public static function write()
    {
        $log =  self::$config;
        $class = new self::$driver($log);
        return $class->save(self::logData());
    }


    /**
     * 日志内容接口
     * @access public
     * @return string
     */
    public static function logData()
    {
        $now         = date('Y-m-d H:i:s');
        if (isset($_SERVER['HTTP_HOST'])) {
            $current_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $current_uri = "cmd:" . implode(' ', $_SERVER['argv']);
        }
        $runtime    = number_format(microtime(true) - APP_START_TIME, 10);
        $reqs       = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $time_str   = ' [运行时间：' . number_format($runtime, 6) . 's][吞吐率：' . $reqs . 'req/s]';
        $memory_use = number_format((memory_get_usage() - APP_START_MEM) / 1024, 2);
        $memory_str = ' [内存消耗：' . $memory_use . 'kb]';
        $file_load  = ' [文件加载：' . count(get_included_files()) . ']';

        $info   = '[ log ] ' . $current_uri . $time_str . $memory_str . $file_load . "\r\n";
        $server = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '0.0.0.0';
        $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
        $uri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $log = self::$log;
        $log['info']['REQUEST'] = $_REQUEST;
        $log['info']['UA'] = $_SERVER['HTTP_USER_AGENT'];
        $msg = '';
        foreach ($log as $type => $val) {
            $msg .= '[ ' . $type . ' ] ' . var_export($val, true) . "\r\n";
        }
        $logData = "[{$now}] {$server} {$remote} {$method} {$uri}\r\n{$info}{$msg}-------------------------------";
        $logData .= "------------------------------------------------------------------------------------\r\n\r\n";
        return $logData;
    }

}
