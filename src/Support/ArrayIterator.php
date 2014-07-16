<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/13
 * Time: 20:01
 */

namespace trochilidae\Sockets\Support;

use Iterator;

abstract class ArrayIterator implements Iterator{

    const IT_MODE_FIFO = 0;
    const IT_MODE_LIFO = 1;

    protected $iteratorMode = self::IT_MODE_FIFO;

    protected $iteratorVariable;

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link Http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->{$this->iteratorVariable});
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link Http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        switch($this->iteratorMode){
            case self::IT_MODE_LIFO:
                prev($this->{$this->iteratorVariable});
                break;
            default:
                next($this->{$this->iteratorVariable});
                break;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link Http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->{$this->iteratorVariable});
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link Http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return !is_null(key($this->{$this->iteratorVariable}));
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link Http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        switch($this->iteratorMode){
            case self::IT_MODE_LIFO:
                end($this->{$this->iteratorVariable});
                break;
            default:
                reset($this->{$this->iteratorVariable});
                break;
        }
    }

}
