<?php


namespace carono\telegram\traits;


use Symfony\Component\Cache\Adapter\FilesystemAdapter;

trait CacheTrait
{
    protected static $cache;
    protected static $cacheFolder;
    protected static $cacheExpire = 1 * 24 * 60; // 1 day
    protected static $cacheClass = FilesystemAdapter::class;
    protected static $cachePrefix = 'cache';

    /**
     * @param $key
     * @param $data
     */
    public static function setCacheValue($key, $data)
    {
        if (static::getCacheExpire()) {
            $item = static::getCache()->getItem(md5(trim($key)));
            $item->set($data);
            static::getCache()->save($item);
        }
    }

    /**
     * @return \Symfony\Component\Cache\Adapter\AbstractAdapter
     */
    public static function getCache()
    {
        if (static::$cache) {
            return static::$cache;
        }
        $cache = new static::$cacheClass(static::$cachePrefix, static::getCacheExpire(), static::getCacheFolder());
        static::$cache = $cache;
        return $cache;
    }

    /**
     * Get cache folder
     *
     * @return string
     */
    public static function getCacheFolder()
    {
        return static::$cacheFolder ?: dirname(__DIR__, 2) . '/cache';
    }


    /**
     * Set cache folder
     *
     * @param string $cacheFolder
     */
    public static function setCacheFolder($cacheFolder)
    {
        static::$cacheFolder = rtrim($cacheFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Get cache expire (in days)
     *
     * @return integer
     */
    public static function getCacheExpire()
    {
        return static::$cacheExpire;
    }

    /**
     * Set cache expire (in days)
     *
     * @param integer $expireInDays
     */
    public static function setCacheExpire($expireInDays)
    {
        static::$cacheExpire = $expireInDays;
    }

    /**
     * @param $text
     * @return mixed
     */
    public static function getCacheValue($text)
    {
        return static::getCache()->getItem(md5(trim($text)))->get();
    }
}