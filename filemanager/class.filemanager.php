<?php
/**
 * @class filemanager
 * @brief Files management class
 *
 * @package Clearbricks
 * @subpackage Filemanager
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class filemanager
{
    public $root;                                               ///< string: Files manager root path
    public $root_url;                                           ///< string: Files manager root URL
    protected $pwd;                                             ///< string: Working (current) director
    protected $exclude_list    = [];                            ///< array: Array of regexps defining excluded items
    protected $exclude_pattern = '';                            ///< string: Files exclusion regexp pattern
    public $dir                = ['dirs' => [], 'files' => []]; ///< array: Current directory content array

    /**
     * Constructor
     *
     * New filemanage istance. Note that filemanage is a jail in given root
     * path. You won't be able to access files outside {@link $root} path with
     * the object's methods.
     *
     * @param string    $root        Root path
     * @param string    $root_url        Root URL
     */
    public function __construct($root, $root_url = '')
    {
        $this->root     = $this->pwd     = path::real($root);
        $this->root_url = $root_url;

        if (!preg_match('#/$#', $this->root_url)) {
            $this->root_url = $this->root_url . '/';
        }

        if (!$this->root) {
            throw new Exception('Invalid root directory.');
        }
    }

    /**
     * Change directory
     *
     * Changes working directory. $dir is relative to instance {@link $root}
     * directory.
     *
     * @param string    $dir            Directory
     */
    public function chdir($dir)
    {
        $realdir = path::real($this->root . '/' . path::clean($dir));
        if (!$realdir || !is_dir($realdir)) {
            throw new Exception('Invalid directory.');
        }

        if ($this->isExclude($realdir)) {
            throw new Exception('Directory is excluded.');
        }

        $this->pwd = $realdir;
    }

    /**
     * Get working directory
     *
     * Returns working directory path.
     *
     * @return string
     */
    public function getPwd()
    {
        return $this->pwd;
    }

    /**
     * Current directory is writable
     *
     * @return boolean    true if working directory is writable
     */
    public function writable()
    {
        if (!$this->pwd) {
            return false;
        }

        return is_writable($this->pwd);
    }

    /**
     * Add exclusion
     *
     * Appends an exclusion to exclusions list. $f should be a regexp.
     *
     * @see $exclude_list
     * @param string    $f            Exclusion regexp
     */
    public function addExclusion($f)
    {
        if (is_array($f)) {
            foreach ($f as $v) {
                if (($V = path::real($v)) !== false) {
                    $this->exclude_list[] = $V;
                }
            }
        } elseif (($F = path::real($f)) !== false) {
            $this->exclude_list[] = $F;
        }
    }

    /**
     * Path is excluded
     *
     * Returns true if path (file or directory) $f is excluded. $f path is
     * relative to {@link $root} path.
     *
     * @see $exclude_list
     * @param string    $f            Path to match
     * @return boolean
     */
    protected function isExclude($f)
    {
        foreach ($this->exclude_list as $v) {
            if (strpos($f, $v) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * File is excluded
     *
     * Returns true if file $f is excluded. $f path is relative to {@link $root}
     * path.
     *
     * @see $exclude_pattern
     * @param string    $f            File to match
     * @return boolean
     */
    protected function isFileExclude($f)
    {
        if (!$this->exclude_pattern) {
            return false;
        }

        return preg_match($this->exclude_pattern, $f);
    }

    /**
     * Item in jail
     *
     * Returns true if file or directory $f is in jail (ie. not outside the
     * {@link $root} directory).
     *
     * @param string    $f            Path to match
     * @return boolean
     */
    protected function inJail($f)
    {
        $f = path::real($f);

        if ($f !== false) {
            return preg_match('|^' . preg_quote($this->root, '|') . '|', $f);
        }

        return false;
    }

    /**
     * File in files
     *
     * Returns true if file $f is in files array of {@link $dir}.
     *
     * @param string    $f            File to match
     * @return boolean
     */
    public function inFiles($f)
    {
        foreach ($this->dir['files'] as $v) {
            if ($v->relname == $f) {
                return true;
            }
        }
        return false;
    }

    /**
     * Directory list
     *
     * Creates list of items in working directory and append it to {@link $dir}
     *
     * @uses sortHandler(), fileItem
     */
    public function getDir()
    {
        $dir = path::clean($this->pwd);

        $dh = @opendir($dir);

        if ($dh === false) {
            throw new Exception('Unable to read directory.');
        }

        $d_res = $f_res = [];

        while (($file = readdir($dh)) !== false) {
            $fname = $dir . '/' . $file;

            if ($this->inJail($fname) && !$this->isExclude($fname)) {
                if (is_dir($fname) && $file != '.') {
                    $tmp = new fileItem($fname, $this->root, $this->root_url);
                    if ($file == '..') {
                        $tmp->parent = true;
                    }
                    $d_res[] = $tmp;
                }

                if (is_file($fname) && strpos($file, '.') !== 0 && !$this->isFileExclude($file)) {
                    $f_res[] = new fileItem($fname, $this->root, $this->root_url);
                }
            }
        }
        closedir($dh);

        $this->dir = ['dirs' => $d_res, 'files' => $f_res];
        usort($this->dir['dirs'], [$this, 'sortHandler']);
        usort($this->dir['files'], [$this, 'sortHandler']);
    }

    /**
     * Root directories
     *
     * Returns an array of directory under {@link $root} directory.
     *
     * @uses fileItem
     * @return array
     */
    public function getRootDirs()
    {
        $d = files::getDirList($this->root);

        $dir = [];

        foreach ($d['dirs'] as $v) {
            $dir[] = new fileItem($v, $this->root, $this->root_url);
        }

        return $dir;
    }

    /**
     * Upload file
     *
     * Move <var>$tmp</var> file to its final destination <var>$dest</var> and
     * returns the destination file path.
     * <var>$dest</var> should be in jail. This method will throw exception
     * if the file cannot be written.
     *
     * You should first verify upload status, with {@link files::uploadStatus()}
     * or PHP native functions.
     *
     * @see files::uploadStatus()
     * @param string    $tmp            Temporary uploaded file path
     * @param string    $dest        Destination file
     * @param string    $overwrite    overwrite mode
     * @return string                Destination real path
     */
    public function uploadFile($tmp, $dest, $overwrite = false)
    {
        $dest = $this->pwd . '/' . path::clean($dest);

        if ($this->isFileExclude($dest)) {
            throw new Exception(__('Uploading this file is not allowed.'));
        }

        if (!$this->inJail(dirname($dest))) {
            throw new Exception(__('Destination directory is not in jail.'));
        }

        if (!$overwrite && file_exists($dest)) {
            throw new Exception(__('File already exists.'));
        }

        if (!is_writable(dirname($dest))) {
            throw new Exception(__('Cannot write in this directory.'));
        }

        if (@move_uploaded_file($tmp, $dest) === false) {
            throw new Exception(__('An error occurred while writing the file.'));
        }

        files::inheritChmod($dest);
        return path::real($dest);
    }

    /**
     * Upload file by bits
     *
     * Creates a new file <var>$name</var> with contents of <var>$bits</var> and
     * return the destination file path.
     * <var>$name</var> should be in jail. This method will throw exception
     * if file cannot be written.
     *
     * @param string    $bits        Destination file content
     * @param string    $name        Destination file
     * @return string                Destination real path
     */
    public function uploadBits($name, $bits)
    {
        $dest = $this->pwd . '/' . path::clean($name);

        if ($this->isFileExclude($dest)) {
            throw new Exception(__('Uploading this file is not allowed.'));
        }

        if (!$this->inJail(dirname($dest))) {
            throw new Exception(__('Destination directory is not in jail.'));
        }

        if (!is_writable(dirname($dest))) {
            throw new Exception(__('Cannot write in this directory.'));
        }

        $fp = @fopen($dest, 'wb');
        if ($fp === false) {
            throw new Exception(__('An error occurred while writing the file.'));
        }

        fwrite($fp, $bits);
        fclose($fp);
        files::inheritChmod($dest);

        return path::real($dest);
    }

    /**
     * New directory
     *
     * Creates a new directory <var>$d</var> relative to working directory.
     *
     * @param string    $d            Directory name
     */
    public function makeDir($d)
    {
        files::makeDir($this->pwd . '/' . path::clean($d));
    }

    /**
     * Move file
     *
     * Moves a file <var>$s</var> to a new destination <var>$d</var>. Both
     * <var>$s</var> and <var>$d</var> are relative to {@link $root}.
     *
     * @param string    $s            Source file
     * @param string    $d            Destination file
     */
    public function moveFile($s, $d)
    {
        $s = $this->root . '/' . path::clean($s);
        $d = $this->root . '/' . path::clean($d);

        if (($s = path::real($s)) === false) {
            throw new Exception(__('Source file does not exist.'));
        }

        $dest_dir = path::real(dirname($d));

        if (!$this->inJail($s)) {
            throw new Exception(__('File is not in jail.'));
        }
        if (!$this->inJail($dest_dir)) {
            throw new Exception(__('File is not in jail.'));
        }

        if (!is_writable($dest_dir)) {
            throw new Exception(__('Destination directory is not writable.'));
        }

        if (@rename($s, $d) === false) {
            throw new Exception(__('Unable to rename file.'));
        }
    }

    /**
     * Remove item
     *
     * Removes a file or directory <var>$f</var> which is relative to working
     * directory.
     *
     * @param string    $f            Path to remove
     */
    public function removeItem($f)
    {
        $file = path::real($this->pwd . '/' . path::clean($f));

        if (is_file($file)) {
            $this->removeFile($f);
        } elseif (is_dir($file)) {
            $this->removeDir($f);
        }
    }

    /**
     * Remove item
     *
     * Removes a file <var>$f</var> which is relative to working directory.
     *
     * @param string    $f            File to remove
     */
    public function removeFile($f)
    {
        $f = path::real($this->pwd . '/' . path::clean($f));

        if (!$this->inJail($f)) {
            throw new Exception(__('File is not in jail.'));
        }

        if (!files::isDeletable($f)) {
            throw new Exception(__('File cannot be removed.'));
        }

        if (@unlink($f) === false) {
            throw new Exception(__('File cannot be removed.'));
        }
    }

    /**
     * Remove item
     *
     * Removes a directory <var>$d</var> which is relative to working directory.
     *
     * @param string    $d            Directory to remove
     */
    public function removeDir($d)
    {
        $d = path::real($this->pwd . '/' . path::clean($d));

        if (!$this->inJail($d)) {
            throw new Exception(__('Directory is not in jail.'));
        }

        if (!files::isDeletable($d)) {
            throw new Exception(__('Directory cannot be removed.'));
        }

        if (@rmdir($d) === false) {
            throw new Exception(__('Directory cannot be removed.'));
        }
    }

    /**
     * SortHandler
     *
     * This method is called by {@link getDir()} to sort files. Can be overrided
     * in inherited classes.
     *
     * @param fileItem    $a            fileItem object
     * @param fileItem    $b            fileItem object
     * @return integer
     */
    protected function sortHandler($a, $b)
    {
        if ($a->parent && !$b->parent || !$a->parent && $b->parent) {
            return ($a->parent) ? -1 : 1;
        }
        return strcasecmp($a->basename, $b->basename);
    }
}

/**
 * @class fileItem
 * @brief File item
 *
 * File item class used by {@link filemanager}. In this class {@link $file} could
 * be either a file or a directory.
 *
 * @package Clearbricks
 * @subpackage Filemanager
 */
class fileItem
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
        $file = path::real($file);
        $stat = stat($file);
        $path = path::info($file);

        $rel = preg_replace('/^' . preg_quote($root, '/') . '\/?/', '', $file);

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
        $this->del = files::isDeletable($file);

        $this->type        = $this->d ? null : files::getMimeType($file);
        $this->type_prefix = preg_replace('/^(.+?)\/.+$/', '$1', $this->type);
    }
}
