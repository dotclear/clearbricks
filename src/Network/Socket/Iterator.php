<?php
/**
 * @class SocketIterator
 * @brief Network socket iterator
 *
 * This class offers an iterator for network operations made with
 * {@link Socket}.
 *
 * @see Socket::write()
 *
 * @package Clearbricks
 * @subpackage Network
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Network\Socket;

use Clearbricks\Common\Exception;

class Iterator implements \Iterator
{
    protected $_handle; ///< resource: Socket resource handler
    protected $_index;  ///< integer: Current index position

    /**
     * Constructor
     *
     * @param resource    $handle        Socket resource handler
     */
    public function __construct(&$handle)
    {
        if (!is_resource($handle)) {
            throw new Exception('Handle is not a resource');
        }
        $this->_handle = &$handle;
        $this->_index  = 0;
    }

    /* Iterator methods
    --------------------------------------------------- */
    /**
     * Rewind
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        # Nothing
    }

    /**
     * Valid
     *
     * @return boolean    True if EOF of handler
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return !feof($this->_handle);
    }

    /**
     * Move index forward
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->_index++;
    }

    /**
     * Current index
     *
     * @return integer    Current index
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_index;
    }

    /**
     * Current value
     *
     * @return string    Current socket response line
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return fgets($this->_handle, 4096);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Network\Socket\Iterator', 'netSocketIterator');
