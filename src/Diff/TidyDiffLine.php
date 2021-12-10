<?php
/**
 * @class TidyDiffLine
 * @brief TIDY diff line
 *
 * A diff line representation. Used by a TIDY chunk.
 *
 * @package Clearbricks
 * @subpackage Diff
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Diff;

class TidyDiffLine
{
    protected $type;    ///< string: Line type
    protected $lines;   ///< array: Line number for old and new context
    protected $content; ///< string: Line content

    /**
     * Constructor
     *
     * Creates a line representation for a tidy chunk.
     *
     * @param string    $type        Tine type
     * @param array        $lines        Line number for old and new context
     * @param string    $content        Line content
     * @return object
     */
    public function __construct($type, $lines, $content)
    {
        $allowed_type = ['context', 'delete', 'insert'];

        if (in_array($type, $allowed_type) && is_array($lines) && is_string($content)) {
            $this->type    = $type;
            $this->lines   = $lines;
            $this->content = $content;
        }
    }

    /**
     * Magic get
     *
     * Returns field content according to the given name, null otherwise.
     *
     * @param string    $n            Field name
     * @return string
     */
    public function __get($n)
    {
        return $this->{$n} ?? null;
    }

    /**
     * Overwrite
     *
     * Overwrites content for the current line.
     *
     * @param string    $content        Line content
     */
    public function overwrite($content)
    {
        if (is_string($content)) {
            $this->content = $content;
        }
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Diff\TidyDiffLine', 'tidyDiffLine');
