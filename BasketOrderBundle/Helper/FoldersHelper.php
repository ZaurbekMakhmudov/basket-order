<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 08.03.17
 * Time: 0:39
 */

namespace App\BasketOrderBundle\Helper;

use Symfony\Component\Filesystem\Filesystem;

class FoldersHelper
{
    /** @var null |Filesystem */
    protected static $fs = null;

    /**
     * @return null|Filesystem
     */
    protected static function getFs()
    {
        if (!static::$fs) {
            $fs = new Filesystem();
            static::$fs = $fs;
        }

        return static::$fs;
    }

    /**
     * @param $folders
     * @return array
     */
    static public function mkDir($folders)
    {
        $fs = static::getFs();
        $out = [];
        if (is_array($folders)) {
            foreach ($folders as $key => $folder) {
                if (!self::existPathFile($folder)) {
                    $fs->mkdir($folder, 0777);
                    $out[] = $key;
                }
            }
        } else {
            if (!self::existPathFile($folders)) {
                $fs->mkdir($folders, 0777);
            }
        }

        return $out;
    }

    /**
     * @param $pathFile
     * @return bool
     */
    static public function existPathFile($pathFile)
    {
        $fs = static::getFs();
        if ($pathFile and $fs->exists($pathFile)) {

            return true;
        }

        return false;
    }
}