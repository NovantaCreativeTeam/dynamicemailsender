<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2f0d9dfdefe83591eae3a60b13a5cc70
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Novanta\\DynamicEmailSender\\' => 27,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Novanta\\DynamicEmailSender\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2f0d9dfdefe83591eae3a60b13a5cc70::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2f0d9dfdefe83591eae3a60b13a5cc70::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit2f0d9dfdefe83591eae3a60b13a5cc70::$classMap;

        }, null, ClassLoader::class);
    }
}
