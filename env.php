<?php
/**
 * Setup application environment
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        if(isset($_ENV[$key])){
            return $_ENV[$key];
        }
        $env = array_merge($_ENV??[],getenv());
        if(isset($env[$key])) {
            return $env[$key];
        }else{
            return $default;
        }
    }
}

defined('YII_DEBUG') or define('YII_DEBUG', env('YII_DEBUG',false) === 'true');
defined('YII_ENV') or define('YII_ENV', env('YII_DEBUG','prod'));
