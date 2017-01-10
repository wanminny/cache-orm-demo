<?php

namespace Orm\MySQL;

//use Hood\Debug\DebugError;

class Root
{
    /**
     * 应用环境
     * @var string
     */
    protected $applicationEnv;

    /**
     * @var null
     */
    private $debugError = null;

    /**
     * 缓存时间
     * @var int
     */
    protected $_cacheExpire = 3600;

    /**
     * 缓存
     * @var bool
     */
    protected $_cacheStatus = true;

    /**
     * 缓存key
     * @var null
     */
    protected $_cacheKey = null;

    /**
     * 缓存tagName
     * @var string
     */
    protected $_cacheTagName = '';


    public function __construct()
    {
        /// 开发;测试;生产环境!
        defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'developer');
        defined('APPLICATION_SYSTEM_CONFIG') || define('APPLICATION_SYSTEM_CONFIG',APP_PATH."/Config");
    }

    /**
     * 获取应用环境
     * @return string
     */
    public function getApplicationEnv()
    {
        return $this->applicationEnv;
    }

    /**
     * 设置应用环境
     * @param $env
     * @return $this
     */
    public function setApplicationEnv($env)
    {
        define('APPLICATION_ENV', $env);
        return $this;
    }

    /**
     *
     * @param $configFile
     * @return $this
     */
    public function setApplicationSystemConfig($configFile)
    {
        define('APPLICATION_SYSTEM_CONFIG', $configFile);
        return $this;
    }

    /**
     * 获取server
     * @param $serviceName
     * @param string $suffix
     * @return Server
     */
    public function getServerHost($serviceName, $suffix = 'config.ini')
    {
        $serviceFileArray = array(
            $serviceName,
            APPLICATION_ENV,
            $suffix
        );
        $serviceFile = APPLICATION_SYSTEM_CONFIG . DIRECTORY_SEPARATOR . implode('.', $serviceFileArray);
        return new Server($serviceFile);
    }

    /**
     * 错误处理
     * @return DebugError
     */
//    public function debugError()
//    {
//        if ($this->debugError == null) {
//            $this->debugError = new DebugError();
//        }
//        return $this->debugError;
//    }
} 