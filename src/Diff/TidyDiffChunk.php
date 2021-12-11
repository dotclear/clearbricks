<?php
/**
 * @class tidyDiffChunk
 * @brief TIDY diff chunk
 *
 * A diff chunk representation. Used by a TIDY diff.
 *
 * @package Clearbricks
 * @subpackage Diff
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Diff;

class TidyDiffChunk
{
    protected $__info; ///< array: Chunk information array
    protected $__data; ///< array: Chunk data array

    /**
     * Constructor
     *
     * Creates and initializes a chunk representation for a TIDY diff.
     */
    public function __construct()
    {
        $this->__info = [
            'context' => 0,
            'delete'  => 0,
            'insert'  => 0,
            'range'   => [
                'start' => [],
                'end'   => []
            ]
        ];
        $this->__data = [];
    }

    /**
     * Set chunk range
     *
     * Sets chunk range in TIDY chunk object.
     *
     * @param integer    $line_start        Old start line number
     * @param integer    $offest_start        Old offset number
     * @param integer    $line_end            new start line number
     * @param integer    $offset_end        New offset number
     */
    public function setRange($line_start, $offest_start, $line_end, $offset_end)
    {
        $this->__info['range']['start'] = [$line_start, $offest_start];
        $this->__info['range']['end']   = [$line_end, $offset_end];
    }

    /**
     * Add line
     *
     * Adds TIDY line object for TIDY chunk object.
     *
     * @param string    $type        Tine type
     * @param array        $lines        Line number for old and new context
     * @param string    $content        Line content
     */
    public function addLine($type, $lines, $content)
    {
        $tidy_line = new TidyDiffLine($type, $lines, $content);

        array_push($this->__data, $tidy_line);
        $this->__info[$type]++;
    }

    /**
     * All lines
     *
     * Returns all lines defined.
     *
     * @return array
     */
    public function getLines()
    {
        return $this->__data;
    }

    /**
     * Chunk information
     *
     * Returns chunk information according to the given name, null otherwise.
     *
     * @param string    $n            Info name
     * @return string
     */
    public function getInfo($n)
    {
        return array_key_exists($n, $this->__info) ? $this->__info[$n] : null;
    }

    /**
     * Find changes
     *
     * Finds changes inside lines for each groups of diff lines. Wraps changes
     * by string \0 and \1
     */
    public function findInsideChanges()
    {
        $groups = $this->getGroups();

        foreach ($groups as $group) {
            $middle = count($group) / 2;
            for ($i = 0; $i < $middle; $i++) {
                $from      = $group[$i];
                $to        = $group[$i + $middle];
                $threshold = $this->getChangeExtent($from->content, $to->content);

                if ($threshold['start'] != 0 || $threshold['end'] != 0) {
                    $start  = $threshold['start'];
                    $end    = $threshold['end'] + strlen($from->content);
                    $offset = $end - $start;
                    $from->overwrite(
                        substr($from->content, 0, $start) . '\0' .
                        substr($from->content, $start, $offset) . '\1' .
                        substr($from->content, $end, strlen($from->content))
                    );
                    $end    = $threshold['end'] + strlen($to->content);
                    $offset = $end - $start;
                    $to->overwrite(
                        substr($to->content, 0, $start) . '\0' .
                        substr($to->content, $start, $offset) . '\1' .
                        substr($to->content, $end, strlen($to->content))
                    );
                }
            }
        }
    }

    private function getGroups()
    {
        $res           = $group           = [];
        $allowed_types = ['delete', 'insert'];
        $delete        = $insert        = 0;

        foreach ($this->__data as $k => $line) {
            if (in_array($line->type, $allowed_types)) {
                array_push($group, $line);
                ${$line->type}++;
            } else {
                if ($delete === $insert && count($group) > 0) {
                    array_push($res, $group);
                }
                $delete = $insert = 0;
                $group  = [];
            }
        }
        if ($delete === $insert && count($group) > 0) {
            array_push($res, $group);
        }

        return $res;
    }

    private function getChangeExtent($str1, $str2)
    {
        $start = 0;
        $limit = min(strlen($str1), strlen($str2));
        while ($start < $limit and $str1[$start] === $str2[$start]) {
            $start++;
        }

        $end   = -1;
        $limit = $limit - $start;

        while (-$end <= $limit && $str1[strlen($str1) + $end] === $str2[strlen($str2) + $end]) {
            $end--;
        }

        return ['start' => $start, 'end' => $end + 1];
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Diff\TidyDiffChunk', 'tidyDiffChunk');
