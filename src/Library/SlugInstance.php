<?php

namespace LicenseBridge\WordPressSDK\Library;

trait SlugInstance
{
    /**
     * Array with singleton instances.
     *
     * @var array
     */
    private static $instances = [];

    /**
     * Instance builder.
     *
     * @param string $slug
     * @return void
     */
    public static function instance($slug)
    {
        if (!isset(self::$instances[$slug])) {
            self::$instances[$slug] = new self($slug);
        }

        return self::$instances[$slug];
    }
}
