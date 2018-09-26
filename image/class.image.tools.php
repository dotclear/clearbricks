<?php
/**
 * @class imageTools
 * @brief Image manipulations
 *
 * Class to manipulate images. Some methods are based on https://dev.media-box.net/big/
 *
 * @package Clearbricks
 * @subpackage Images
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class imageTools
{
    public $res; ///< resource: Image resource
    public $memory_limit = null;

    /**
     * Constructor, no parameters.
     */
    public function __construct()
    {
        if (!function_exists('imagegd2')) {
            throw new Exception('GD is not installed');
        }
        $this->res = null;
    }

    /**
     * Close
     *
     * Destroy image resource
     */
    public function close()
    {
        if (is_resource($this->res)) {
            imagedestroy($this->res);
        }

        if ($this->memory_limit) {
            ini_set('memory_limit', $this->memory_limit);
        }
    }

    /**
     * Load image
     *
     * Loads an image content in memory and set {@link $res} property.
     *
     * @param string    $f        Image file path
     */
    public function loadImage($f)
    {
        if (!file_exists($f)) {
            throw new Exception('Image doest not exists');
        }

        if (($info = @getimagesize($f)) !== false) {
            $this->memoryAllocate(
                $info[0], $info[1],
                isset($infos['channels']) ? $info['channels'] : 4
            );

            switch ($info[2]) {
                case 3:
                    $this->res = @imagecreatefrompng($f);
                    if (is_resource($this->res)) {
                        @imagealphablending($this->res, false);
                        @imagesavealpha($this->res, true);
                    }
                    break;
                case 2:
                    $this->res = @imagecreatefromjpeg($f);
                    break;
                case 1:
                    $this->res = @imagecreatefromgif($f);
                    break;
            }
        }

        if (!is_resource($this->res)) {
            throw new Exception('Unable to load image');
        }
    }

    /**
     * Image width
     *
     * @return integer            Image width
     */
    public function getW()
    {
        return imagesx($this->res);
    }

    /**
     * Image height
     *
     * @return integer            Image height
     */
    public function getH()
    {
        return imagesy($this->res);
    }

    public function memoryAllocate($w, $h, $bpp = 4)
    {
        $mem_used  = function_exists('memory_get_usage') ? @memory_get_usage() : 4000000;
        $mem_limit = @ini_get('memory_limit');
        if ($mem_limit && trim($mem_limit) === '-1' || !files::str2bytes($mem_limit)) {
            // Cope with memory_limit set to -1 in PHP.ini
            return;
        }
        if ($mem_used && $mem_limit) {
            $mem_limit  = files::str2bytes($mem_limit);
            $mem_avail  = $mem_limit - $mem_used - (512 * 1024);
            $mem_needed = $w * $h * $bpp;

            if ($mem_needed > $mem_avail) {
                if (@ini_set('memory_limit', $mem_limit + $mem_needed + $mem_used) === false) {
                    throw new Exception(__('Not enough memory to open image.'));
                }

                if (!$this->memory_limit) {
                    $this->memory_limit = $mem_limit;
                }
            }
        }
    }

    /**
     * Image output
     *
     * Returns image content in a file or as HTML output (with headers)
     *
     * @param string         $type        Image type (png or jpg)
     * @param string|null    $file        Output file. If null, output will be echoed in STDOUT
     * @param integer        $qual        JPEG image quality
     */
    public function output($type = 'png', $file = null, $qual = 90)
    {
        if (!$file) {
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            switch (strtolower($type)) {
                case 'png':
                    header('Content-type: image/png');
                    imagepng($this->res);
                    return true;
                case 'jpeg':
                case 'jpg':
                    header('Content-type: image/jpeg');
                    imagejpeg($this->res, null, $qual);
                    return true;
                default:
                    return false;
            }
        } elseif (is_writable(dirname($file))) {
            switch (strtolower($type)) {
                case 'png':
                    return imagepng($this->res, $file);
                case 'jpeg':
                case 'jpg':
                    return imagejpeg($this->res, $file, $qual);
                default:
                    return false;
            }
        }
        return false;
    }

    /**
     * Resize image
     *
     * @param mixed         $WIDTH          Image width (px or percent)
     * @param mixed         $HEIGHT         Image height (px or percent)
     * @param string        $MODE           Crop mode (force, crop, ratio)
     * @param boolean       $EXPAND         Allow resize of image
     */
    public function resize($WIDTH, $HEIGHT, $MODE = 'ratio', $EXPAND = false)
    {

        $imgWidth  = $this->getW();
        $imgHeight = $this->getH();

        if (strpos($WIDTH, '%', 0)) {
            $WIDTH = $imgWidth * $WIDTH / 100;
        }

        if (strpos($HEIGHT, '%', 0)) {
            $HEIGHT = $imgHeight * $HEIGHT / 100;
        }

        $ratio = $imgWidth / $imgHeight;

        // guess resize ($_w et $_h)
        if ($MODE == 'ratio') {
            $_w = 99999;
            if ($HEIGHT > 0) {
                $_h = $HEIGHT;
                $_w = $_h * $ratio;
            }
            if ($WIDTH > 0 && $_w > $WIDTH) {
                $_w = $WIDTH;
                $_h = $_w / $ratio;
            }

            if (!$EXPAND && $_w > $imgWidth) {
                $_w = $imgWidth;
                $_h = $imgHeight;
            }
        } else {
            // crop source image
            $_w = $WIDTH;
            $_h = $HEIGHT;
        }

        if ($MODE == 'force') {
            if ($WIDTH > 0) {
                $_w = $WIDTH;
            } else {
                $_w = $HEIGHT * $ratio;
            }

            if ($HEIGHT > 0) {
                $_h = $HEIGHT;
            } else {
                $_h = $WIDTH / $ratio;
            }

            if (!$EXPAND && $_w > $imgWidth) {
                $_w = $imgWidth;
                $_h = $imgHeight;
            }

            $cropW  = $imgWidth;
            $cropH  = $imgHeight;
            $decalW = 0;
            $decalH = 0;
        } else {
            // guess real viewport of image
            $innerRatio = $_w / $_h;
            if ($ratio >= $innerRatio) {
                $cropH  = $imgHeight;
                $cropW  = $imgHeight * $innerRatio;
                $decalH = 0;
                $decalW = ($imgWidth - $cropW) / 2;
            } else {
                $cropW  = $imgWidth;
                $cropH  = $imgWidth / $innerRatio;
                $decalW = 0;
                $decalH = ($imgHeight - $cropH) / 2;
            }
        }

        if ($_w < 1) {
            $_w = 1;
        }
        if ($_h < 1) {
            $_h = 1;
        }

        # truecolor is 24 bit RGB, ie. 3 bytes per pixel.
        $this->memoryAllocate($_w, $_h, 3);
        $dest = imagecreatetruecolor($_w, $_h);
        $fill = imagecolorallocate($dest, 128, 128, 128);
        imagefill($dest, 0, 0, $fill);
        @imagealphablending($dest, false);
        @imagesavealpha($dest, true);
        imagecopyresampled($dest, $this->res, 0, 0, $decalW, $decalH, $_w, $_h, $cropW, $cropH);
        imagedestroy($this->res);
        $this->res = $dest;
        return true;
    }
}
