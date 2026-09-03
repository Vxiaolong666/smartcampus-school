<?php

/**
 *
 * 自动载入函数
 */
class Autoloader
{
    /**
     * 向PHP注册在自动载入函数
     */
    public static function register()
    {
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * 根据类名载入所在文件
     */
    public static function autoload($className)
    {

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . $className;
        $filePath = str_replace('\\', DIRECTORY_SEPARATOR, $filePath) . '.php';
        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
        
        $funsionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'funsion' . DIRECTORY_SEPARATOR . $className . '.php';
        if (file_exists($funsionPath)) {
            require_once $funsionPath;
            return;
        }
        
        echo "无法加载" . $filePath;
    }
}