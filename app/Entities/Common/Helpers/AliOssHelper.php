<?php

namespace App\Entities\Common\Helpers;

use OSS\Core\OssException;
use OSS\OssClient;

class AliOssHelper
{
    public static $ossInstance;
    public static $bucket;

    public static function __callStatic( $name , $arguments )
    {
    }

    /**
     * @return OssClient
     * @throws \OSS\Core\OssException
     */
    public static function getOssInstance()
    {
        $access_id  = config ( 'filesystems.disks.oss.access_id' );
        $access_key = config ( 'filesystems.disks.oss.access_key' );
        $endpoint   = config ( 'filesystems.disks.oss.endpoint' );
        $isCname    = config ( 'filesystems.disks.oss.isCName' );
        $ssl        = config ( 'filesystems.disks.oss.ssl' );

        if ( is_null ( self::$ossInstance ) ) {
            self::$ossInstance = new OssClient( $access_id , $access_key , $endpoint , $isCname );
        }

        return self::$ossInstance;
    }

    private static function getBucket()
    {

        static $bucket;
        if ( is_null ( $bucket ) ) {
            $bucket = config ( 'filesystems.disks.oss.bucket' );
        }
        return $bucket;
    }

    public static function putFile( $targetFileName , $localFileName , $options = null )
    {
        try {
            self::getOssInstance ()->uploadFile ( self::getBucket () , $targetFileName , $localFileName , $options );
        } catch ( OssException $ossException ) {
            \helper::exceptionToLog ( $ossException );
            return false;
        }

        return true;
    }

    public static function putFileContents( $targetFileName , $contents , $options = null )
    {
        try {
            self::getOssInstance ()->putObject ( self::getBucket () , $targetFileName , $contents , $options );
        } catch ( OssException $ossException ) {
            \helper::exceptionToLog ( $ossException );
            return false;
        }

        return true;
    }

    public static function getUrl( $url , $options = null )
    {
        $signUrl = self::getOssInstance ()->signUrl (self::getBucket (),$url,3600,OssClient::OSS_HTTP_GET,$options);
        return $signUrl;
    }

}
