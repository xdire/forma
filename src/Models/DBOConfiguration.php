<?php namespace Xdire\Forma\Models;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 2/16/17
 */

class DBOConfiguration
{

    /** @var string */
    private $name = "";
    /** @var null | string */
    private $host = null;
    /** @var null | int */
    private $port = null;
    /** @var null | string */
    private $socket = null;
    /** @var null | string */
    private $instance = null;
    /** @var null | string */
    private $user = null;
    /** @var null | string */
    private $password = null;

    function __construct($host = 'localhost', $port = 3306, $socket = null, $instance = null,
                         $user = null, $password = null) {

        $this->host = $host;
        $this->port = $port;
        $this->socket = $socket;
        $this->instance = $instance;
        $this->user = $user;
        $this->password = $password;

    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param null|string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int|null $port
     */
    public function setPort($port)
    {
        $this->port = (int) $port;
    }

    /**
     * @return null|string
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param null|string $socket
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return null|string
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param null|string $instance
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
    }

    /**
     * @return null|string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null|string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param null|string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

}