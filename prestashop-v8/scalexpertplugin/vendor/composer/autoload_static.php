<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitaeede27128c157f15be4481e790e5d5d
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'ScalexpertPlugin\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ScalexpertPlugin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitaeede27128c157f15be4481e790e5d5d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitaeede27128c157f15be4481e790e5d5d::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
