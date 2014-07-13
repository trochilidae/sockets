<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/23
 * Time: 7:45
 */

namespace trochilidae\Sockets;


use trochilidae\Sockets\Exceptions\InvalidArgumentException;

class ProtocolList extends \SplDoublyLinkedList {

    /**
     * @var array
     */
    protected $values = [];

    protected $handshakeProtocols;

    public function __construct(){
        $this->handshakeProtocols = new \SplObjectStorage();
    }

    public function requiresHandshake(){
        return $this->handshakeProtocols->count() == count($this->values);
    }

    public function setIteratorMode ($mode) {
        parent::setIteratorMode($mode);
        $this->rewind();
    }

    /**
     * (PHP 5 &gt;= 5.3.0)<br/>
     * Pops a node from the end of the doubly linked list
     * @link http://php.net/manual/en/spldoublylinkedlist.pop.php
     * @return mixed The value of the popped node.
     */
    public function pop () {
        $value = array_pop($this->values);
        $this->detachHandshakeProtocol($value);
        return parent::pop();
    }

    /**
     * (PHP 5 &gt;= 5.3.0)<br/>
     * Shifts a node from the beginning of the doubly linked list
     * @link http://php.net/manual/en/spldoublylinkedlist.shift.php
     * @return mixed The value of the shifted node.
     */
    public function shift () {
        $value = array_shift($this->values);
        $this->detachHandshakeProtocol($value);
        return parent::shift();
    }

    /**
     * (PHP 5 &gt;= 5.3.0)<br/>
     * Pushes an element at the end of the doubly linked list
     * @link http://php.net/manual/en/spldoublylinkedlist.push.php
     *
     * @param Protocol $value <p>
     *                        The value to push.
     *                        </p>
     *
     * @throws Exceptions\InvalidArgumentException
     * @return void
     */
    public function push ($value) {
        if(!$value instanceof Protocol){
            throw new InvalidArgumentException();
        }
        $this->values[] = $value;
        $this->attachHandshakeProtocol($value);
        parent::push($value);
    }

    /**
     * (PHP 5 &gt;= 5.3.0)<br/>
     * Prepends the doubly linked list with an element
     * @link http://php.net/manual/en/spldoublylinkedlist.unshift.php
     *
     * @param Protocol $value <p>
     *                        The value to unshift.
     *                        </p>
     *
     * @throws Exceptions\InvalidArgumentException
     * @return void
     */
    public function unshift ($value) {
        if(!$value instanceof Protocol){
            throw new InvalidArgumentException();
        }
        $this->attachHandshakeProtocol($value);
        array_unshift($this->values, $value);
        parent::unshift($value);
    }

    /**
     * (PHP 5 &gt;= 5.3.0)<br/>
     * Sets the value at the specified $index to $newval
     * @link http://php.net/manual/en/spldoublylinkedlist.offsetset.php
     *
     * @param mixed $index  <p>
     *                      The index being set.
     *                      </p>
     * @param mixed $newval <p>
     *                      The new value for the <i>index</i>.
     *                      </p>
     *
     * @throws Exceptions\InvalidArgumentException
     * @return void
     */
    public function offsetSet ($index, $newval) {
        if(!is_int($index) && !$newval instanceof Protocol){
            throw new InvalidArgumentException();
        }
        if(isset($this->values[$index])){
            unset($this[$index]);
        }
        $this->values[$index] = $newval;
        $this->attachHandshakeProtocol($newval);
        parent::offsetSet($index, $newval);
    }

    /**
     * (PHP 5 &gt;= 5.3.0)<br/>
     * Unsets the value at the specified $index
     * @link http://php.net/manual/en/spldoublylinkedlist.offsetunset.php
     * @param mixed $index <p>
     * The index being unset.
     * </p>
     * @return void
     */
    public function offsetUnset ($index) {
        $value = $this->values[$index];
        $this->detachHandshakeProtocol($value);
        unset($this->values[$index]);
        parent::offsetUnset($index);
    }

    /**
     * @param Protocol $protocol
     * @param bool     $inclusive
     *
     * @return ProtocolList
     */
    public function filterByProtocol(Protocol $protocol, $inclusive = true){
        $key = array_search($protocol, $this->values);

        $values = [];
        if($key !== false){
            if($inclusive){
                $key++;
            }
            $values = array_slice($this->values, 0, $key);
        }

        $self = new self();
        array_map(function(Protocol $protocol) use($self){
            $self->push($protocol);
        }, $values);
        return $self;
    }

    protected function attachHandshakeProtocol(Protocol $value){
        if($value->requiresHandshake()){
            $this->handshakeProtocols->attach($value);
        }
    }

    protected function detachHandshakeProtocol(Protocol $value){
        if($value->requiresHandshake()){
            $this->handshakeProtocols->detach($value);
        }
    }

} 