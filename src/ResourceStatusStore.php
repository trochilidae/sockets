<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/11
 * Time: 11:40
 */

namespace trochilidae\Sockets;


class ResourceStatusStore
{

    const STATUS_CONNECTING = 0;
    const STATUS_CONNECTED = 1;
    const STATUS_DISCONNECTED = 2;
    const STATUS_CLOSING = 3;
    const STATUS_CLOSED = 4;
    const STATUS_PAUSED = 5;
    const STATUS_PAUSED_READ = 6;
    const STATUS_PAUSED_WRITE = 7;

    protected $rememberStatusWhen = [
        self::STATUS_PAUSED,
        self::STATUS_PAUSED_READ,
        self::STATUS_PAUSED_WRITE,
    ];

    protected $status;

    protected $rememberedStatus;

    protected static $defaultStatus = ResourceStatusStore::STATUS_DISCONNECTED;

    function __construct()
    {
        $this->status = static::$defaultStatus;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        if (in_array($status, $this->rememberStatusWhen) && !in_array($this->status, $this->rememberStatusWhen)) {
            $this->rememberedStatus = $this->status;
        }
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function rememberPreviousStatus()
    {
        if (!is_null($this->rememberedStatus)) {
            $this->status = $this->rememberedStatus;
            $this->rememberedStatus = null;
            return true;
        }
        return false;
    }

} 