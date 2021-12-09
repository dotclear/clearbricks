<?php
/**
 * @class Autoloader
 * @brief Helper to autoload class using php namespace
 *
 * Based on PSR-4 Autoloader
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
 *
 * A root prefix and base directory can be added to all ns
 * to work with non full standardized project.
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

namespace Clearbricks\Common;

class Autoloader
{
    /** Directory separator */
    public const DIR_SEP = DIRECTORY_SEPARATOR;

    /** Namespace separator */
    public const NS_SEP = '\\';

    /** @var string Root namespace prepend to added ns */
    protected $root_prefix = '';

    /** @var string Root directory prepend to added ns */
    protected $root_base_dir = '';

    /** @var array Array of registered namespace [prefix=[base dir]] */
    protected $prefixes = [];

    /**
     * Register loader with SPL autoloader stack.
     *
     * @param string    $root_prefix    Common ns prefix
     * @param string    $root_base_dir  Common dir prefix
     */
    public function __construct(string $root_prefix = '', string $root_base_dir = '')
    {
        if (!empty($root_prefix)) {
            $this->root_prefix = static::normalizePrefix($root_prefix);
        }
        if (!empty($root_base_dir)) {
            $this->root_base_dir = static::normalizeBaseDir($root_base_dir);
        }

        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Get root prefix
     *
     * @return string Root prefix
     */
    public function getRootPrefix(): string
    {
        return $this->root_prefix;
    }

    /**
     * Get root base directory
     *
     * @return string Root base directory
     */
    public function getRootBaseDir(): string
    {
        return $this->root_base_dir;
    }

    /**
     * Normalize namespace prefix
     *
     * @param string    $prefix     Ns prefix
     *
     * @return string Prefix with only right namesapce separator
     */
    public static function normalizePrefix(string $prefix): string
    {
        return ucfirst(trim($prefix, self::NS_SEP)) . self::NS_SEP;
    }

    /**
     * Normalize base directory
     *
     * @param string    $base_dir     Dir prefix
     *
     * @return string Base dir with right directory separator
     */
    public static function normalizeBaseDir(string $base_dir): string
    {
        return rtrim($base_dir, self::DIR_SEP) . self::DIR_SEP;
    }

    /**
     * Clean up a string into namespace part
     *
     * @param string    $str    string to clean
     *
     * @return string|null   Cleaned string or null if empty
     */
    public static function qualifyNamespace(string $str): ?string
    {
        $str = preg_replace(
            [
                '/[^a-zA-Z0-9_' . preg_quote(self::NS_SEP) . ']/',
                '/[' . preg_quote(self::NS_SEP) . ']{2,}/',
            ],
            [
                '',
                self::NS_SEP,
            ],
            $str
        );

        return !$str ? null : static::normalizePrefix($str);
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix The namespace prefix.
     * @param string $base_dir A base directory for class files in the namespace.
     * @param bool $prepend If true, prepend the base directory to the stack
     * instead of appending it; this causes it to be searched first rather
     * than last.
     *
     * @return void
     */
    public function addNamespace(string $prefix, string $base_dir, bool $prepend = false): void
    {
        $prefix   = $this->root_prefix . static::normalizePrefix($prefix);
        $base_dir = $this->root_base_dir . static::normalizeBaseDir($base_dir);

        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = [];
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }

    /**
     * Get list of registered namespace
     *
     * @return array List of namesapce prefix / base dir
     */
    public function getNamespaces(): array
    {
        return $this->prefixes;
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     *
     * @return mixed The mapped file name on success, or boolean false on failure.
     */
    public function loadClass(string $class)
    {
        $prefix = $class;

        while (false !== $pos = strrpos($prefix, self::NS_SEP)) {
            $prefix         = substr($class, 0, $pos + 1);
            $relative_class = substr($class, $pos + 1);

            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }

            $prefix = rtrim($prefix, self::NS_SEP);
        }

        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix The namespace prefix.
     * @param string $relative_class The relative class name.
     *
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile(string $prefix, string $relative_class): mixed
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $base_dir) {
            $file = $base_dir
                  . str_replace(self::NS_SEP, self::DIR_SEP, $relative_class)
                  . '.php';

            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     *
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;

            return true;
        }

        return false;
    }
}
