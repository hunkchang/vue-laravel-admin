<?php

namespace App\Entities\Common\Helpers;

use Input;
use Models\Enum\Files\FileStdClass;
use Models\Enum\Files\FileType;

class FileHelper
{
    /**
     * @param        $name
     * @param string $dir
     * @return bool|FileStdClass
     */
    public static function uploadFile( $name , $dir = FileType::IMAGES )
    {
        if ( $dir == FileType::IMAGES ) {
            $maxSize = config ( 'filesystems.disks.local.max_image_size' );
        } else {
            $maxSize = config ( 'filesystems.disks.local.max_file_size' );
        }

        $file = Input::file ( $name );
        $size = $file->getSize ();
        //超过文件限制大小则返回错误提示
        if ( $size > $maxSize ) {
            \Log::error ( trans ( 'admin::common.assets.uploadFailedsize' ) );
            return false;
        }
        try {
            if ( !empty( $file ) && $file->isValid () ) {
                //上传图片
                //获取文件扩展名
                $extension = $file->getClientOriginalExtension ();
                //获取文件上传目录
                $targetFileDir = \helper::getFilePath ();
                //生成文件名
                $fileName = \helper::getFileName () . '.' . $extension;
                //获取文件的相对路径名称,用于获取文件远程地址
                $relativeImagePath = $targetFileDir . $fileName;
                //获取文件的绝对路径
                $getAbsoluteUrl = \helper::getAbsolutePath ( $relativeImagePath );
                //文件类型
                $mimeType = $file->getMimeType ();
                //验证文件
                $allowExtends = config ( 'filesystems.disks.local.allow_extends' );
                if ( !in_array ( $mimeType , $allowExtends ) ) {
                    //提示文件错误
                    \Log::error ( "文件格式不对" );
                    return false;
                }
                //自动生成目录
                $targetFileDir = \helper::getAbsolutePath ( $targetFileDir , \Storage::path ( $dir ) );
                if ( !file_exists ( $targetFileDir ) ) {
                    mkdir ( $targetFileDir , '755' , true );
                }
                //上传图片
                if ( $file->move ( $targetFileDir , $fileName ) ) {
                    //上次图片到云
                    if ( !$result = \helper::putFile ( $relativeImagePath , $getAbsoluteUrl ) ) {
                        $result = \helper::putFile ( $relativeImagePath , $getAbsoluteUrl ); //上次失败重新上传
                    }
                    if ( !$result ) {
                        \Log::error ( '上传失败,请联系管理员' );
                        return false;
                    }
                    $newFile                    = new FileStdClass();
                    $newFile->setRelativeImagePath ($relativeImagePath);
                    $newFile->setSize ($size);
                    $newFile->setFilename ($fileName);
                    $newFile->setExtension ($extension);

                    return $newFile;

                } else {
                    \Log::error ( trans ( 'admin::common.assets.uploadFailed' ) );
                    return false;
                }
            }
        } catch ( \Exception $exception ) {
            \helper::exceptionToLog ( $exception );
            return false;
        }

    }
}
