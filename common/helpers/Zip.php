<?php

namespace common\helpers;

class Zip
{

    /**
     * Zip a folder (include itself).
     * Usage:
     *   Zip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     *
     * @param string $sourcePath Path of directory to be zip.
     * @param string $outZipPath Path of output zip file.
     */
    public static function zipDir($sourcePath, $outZipPath)
    {
        $pathInfo   = self::myPathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName    = $pathInfo['basename'];
        $sourcePath = $parentPath . '/' . $dirName;//防止传递'folder' 文件夹产生bug
        $z          = new \ZipArchive();
        $z->open($outZipPath, \ZIPARCHIVE::CREATE);//建立zip文件
        $z->addEmptyDir($dirName);//建立文件夹
        self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
        $z->close();
    }

    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // 添加子文件夹
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    private static function myPathInfo($filepath)
    {
        $pathParts               = array();
        $pathParts ['dirname']   = rtrim(substr($filepath, 0, strrpos($filepath, '/')), "/") . "/";
        $pathParts ['basename']  = ltrim(substr($filepath, strrpos($filepath, '/')), "/");
        $pathParts ['extension'] = substr(strrchr($filepath, '.'), 1);
        $pathParts ['filename']  = ltrim(substr($pathParts ['basename'], 0, strrpos($pathParts ['basename'], '.')), "/");
        return $pathParts;
    }
}