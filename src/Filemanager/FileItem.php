<?php
/**
 * @class fileItem
 * @brief File item
 *
 * File item class used by {@link filemanager}. In this class {@link $file} could
 * be either a file or a directory.
 *
 * @package Clearbricks
 * @subpackage Filemanager
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\FileManager;

use Clearbricks\Common\Path;
use Clearbricks\Common\Files;

class FileItem
{
    public $file;           ///< string: Complete path to file
    public $basename;       ///< string: File basename
    public $dir;            ///< string: File directory name
    public $file_url;       ///< string: File URL
    public $dir_url;        ///< string: File directory URL
    public $extension;      ///< string: File extension
    public $relname;        ///< string: File path relative to <var>$root</var> given in constructor
    public $parent = false; ///< boolean: Parent directory (ie. "..")
    public $type;           ///< string: File MimeType. See {@link files::getMimeType()}.
    public $type_prefix;    ///< string
    public $mtime;          ///< integer: File modification timestamp
    public $size;           ///< integer: File size
    public $mode;           ///< integer: File permissions mode
    public $uid;            ///< integer: File owner ID
    public $gid;            ///< integer: File group ID
    public $w;              ///< boolean: True if file or directory is writable
    public $d;              ///< boolean: True if file is a directory
    public $x;              ///< boolean: True if file file is executable or directory is traversable
    public $f;              ///< boolean: True if file is a file
    public $del;            ///< boolean: True if file or directory is deletable

    /**
     * Constructor
     *
     * Creates an instance of fileItem object.
     *
     * @param string    $file        Absolute file or directory path
     * @param string    $root        File root path
     * @param string    $root_url        File root URL
     */
    public function __construct($file, $root, $root_url = '')
    {
        $file = Path::real($file);
        $stat = stat($file);
        $path = Path::info($file);

        $rel = preg_replace('/^' . preg_quote($root, '/') . '\/?/', '', (string) $file);

        $this->file     = $file;
        $this->basename = $path['basename'];
        $this->dir      = $path['dirname'];
        $this->relname  = $rel;

        $this->file_url = str_replace('%2F', '/', rawurlencode($rel));
        $this->file_url = $root_url . $this->file_url;

        $this->dir_url   = dirname($this->file_url);
        $this->extension = $path['extension'];

        $this->mtime = $stat[9];
        $this->size  = $stat[7];
        $this->mode  = $stat[2];
        $this->uid   = $stat[4];
        $this->gid   = $stat[5];
        $this->w     = is_writable($file);
        $this->d     = is_dir($file);
        $this->f     = is_file($file);
        if ($this->d) {
            $this->x = file_exists($file . '/.');
        } else {
            $this->x = false;
        }
        $this->del = Files::isDeletable($file);

        $this->type        = $this->d ? null : Files::getMimeType($file);
        $this->type_prefix = preg_replace('/^(.+?)\/.+$/', '$1', (string) $this->type);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\FileManager\FileItem', 'fileItem');
