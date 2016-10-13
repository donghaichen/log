<?php
/**
 * Clover Log  Clover Log 日志写入驱动
 *
 * @author Donghaichen [<chendongahai888@gmail.com>]
 * @todo psr/log规范日志接口
 */

namespace Clovers\Log\Driver;
class File
{
    protected $config = [
        'file_size'   => 10485760, //单位字节
    ];

    // 实例化并传入参数
    public function __construct($config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 日志写入接口
     * @access public
     * @param string
     * @return bool
     */
    public function save($logData)
    {
        $path =$this->config['path'];
        $file = isset($this->config['type']) ?  $path .  $this->config['type'] . '/' : $path ;
        $destination = $file . date('Ym') . '/' . date('d') . '.log';
        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            rename($destination, dirname($destination) . '/' . date('YmdHis') . '-' . basename($destination));
        }
        return error_log($logData, 3, $destination);
     }

}
