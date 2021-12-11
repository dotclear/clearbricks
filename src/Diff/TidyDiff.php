<?php
/**
 * @class tidyDiff
 * @brief TIDY diff
 *
 * A TIDY diff representation
 *
 * @package Clearbricks
 * @subpackage Diff
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Diff;

class TidyDiff
{
    protected $__data = []; ///< array: Chunks array

    private $up_range = '/^@@ -([\d]+),([\d]+) \+([\d]+),([\d]+) @@/m';
    private $up_ctx   = '/^ (.*)$/';
    private $up_ins   = '/^\+(.*)$/';
    private $up_del   = '/^-(.*)$/';

    /**
     * Constructor
     *
     * Creates a diff representation from unified diff.
     *
     * @param string    $udiff            Unified diff
     * @param boolean   $inline_changes   Find inline changes
     */
    public function __construct($udiff, $inline_changes = false)
    {
        Diff::uniCheck($udiff);

        preg_match_all($this->up_range, $udiff, $context);

        $chunks = preg_split($this->up_range, $udiff, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chunks as $k => $chunk) {
            $tidy_chunk = new TidyDiffChunk();
            $tidy_chunk->setRange(
                (int) $context[1][$k],
                (int) $context[2][$k],
                (int) $context[3][$k],
                (int) $context[4][$k]
            );

            $old_line = (int) $context[1][$k];
            $new_line = (int) $context[3][$k];

            foreach (explode("\n", $chunk) as $line) {
                # context
                if (preg_match($this->up_ctx, $line, $m)) {
                    $tidy_chunk->addLine('context', [$old_line, $new_line], $m[1]);
                    $old_line++;
                    $new_line++;
                }
                # insertion
                if (preg_match($this->up_ins, $line, $m)) {
                    $tidy_chunk->addLine('insert', [$old_line, $new_line], $m[1]);
                    $new_line++;
                }
                # deletion
                if (preg_match($this->up_del, $line, $m)) {
                    $tidy_chunk->addLine('delete', [$old_line, $new_line], $m[1]);
                    $old_line++;
                }
            }

            if ($inline_changes) {
                $tidy_chunk->findInsideChanges();
            }

            array_push($this->__data, $tidy_chunk);
        }
    }

    /**
     * All chunks
     *
     * Returns all chunks defined.
     *
     * @return array
     */
    public function getChunks()
    {
        return $this->__data;
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Diff\TidyDiff', 'tidyDiff');
