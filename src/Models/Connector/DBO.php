<?php namespace Xdire\Forma\Models\Connector;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 2/16/17
 */

use Xdire\Forma\Exceptions\DBOConfigurationException;
use Xdire\Forma\Exceptions\DBOReadException;
use Xdire\Forma\Exceptions\DBOWriteException;
use Xdire\Forma\Models\DBOConfiguration;

class DBO
{

    protected static $dbm = null;

    /** @var null|\PDO  */
    protected $db = null;

    /** @var int */
    protected $rowsSelected = 0;

    /** @var int */
    protected $rowsAffected = 0;

    /**
     * PDOWrapper constructor.
     * @param   DBOConfiguration $conf
     * @throws  DBOConfigurationException
     */
    function __construct(DBOConfiguration $conf = null) {

        if(self::$dbm === null) {
            self::$dbm = new DBOManager();
        }

        // Legacy support
        $dbm = self::$dbm;

        if($conf !== null) {

            $port = $conf->getPort() !== null ? $conf->getPort() : '';
            $host = $conf->getHost() !== null ? $conf->getHost() : '';
            $inst = $conf->getInstance() !== null ? $conf->getInstance() : '';

            $user = $conf->getUser() !== null ? $conf->getUser() : '';
            $pwd = $conf->getPassword() !== null ? $conf->getPassword() : '';

            // Prepare standard variables
            $portPart = 'port=' . $port . ';';
            $hostPart = 'mysql:host=' . $host . ';';

            if ($conf->getSocket() !== null) {
                $host = 'mysql:unix_socket=' . $conf->getSocket() . ';';
            }

            try {

                if(!($this->db = $dbm::getDbInstance($host, (int)$port, $inst))) {

                    $this->db = new \PDO(
                        $hostPart . $portPart . 'dbname=' . $inst, $user, $pwd,
                        [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);

                    $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                    $dbm::addInstance($host, (int)$port, $inst, $this->db);

                }

            } catch (\PDOException $e) {
                throw new DBOConfigurationException("DBO can't create connection to storage. Cause: " .
                    $e->getMessage(), 500);

            }

        } else {

            if(!($this->db = $dbm::getDbInstance(null,null,null))) {

                throw new DBOConfigurationException("DBO wasn't propertly intialized. Cause: PDO not defined", 500);

            }

        }

    }

    /**
     * Raw select
     *
     * @param $statement
     * @return \PDOStatement
     * @throws DBOReadException
     */
    protected function read($statement) {

        try {

            $query = $this->db->prepare($statement);
            $result = $query->execute();
            $this->rowsSelected = $query->rowCount();

            if($result) {
                return $query;
            } else
                throw new DBOReadException("Data read was failed Error: "
                    .$query->errorInfo()." [".$query->errorCode()."]", 400);

        } catch (\PDOException $e) {

            throw new DBOReadException("Data read was failed", 500);

        }

    }

    /**
     * Raw insert
     *
     * @param $statement
     * @return \PDOStatement
     * @throws DBOWriteException
     */
    protected function write($statement) {

        $this->rowsAffected = 0;

        try {

            $query = $this->db->prepare($statement);
            $result = $query->execute();

            if ($result) {
                $this->rowsAffected = $query->rowCount();
                return $query;
            }
            else
                throw new DBOWriteException("Data write was failed. Error: "
                    .$query->errorInfo()." [".$query->errorCode()."]", 500);

        } catch (\PDOException $e) {

            if($e->getCode() == 23000)
                throw new DBOWriteException("Data can't be written because of duplication", 409);
            else
                throw new DBOWriteException("Data write was failed", 500);

        }

    }

    /**
     * @param \PDOStatement $s
     * @return array|null
     */
    protected static function fetchRow($s)
    {
        if($row = $s->fetch(2))
            return $row;
        return null;
    }

    /**
     * @return null|\PDO
     */
    protected function __getInstance()
    {
        return $this->db;
    }

    /**
     * @return int|string
     */
    protected function getLastInsertId() {
        return $this->db->lastInsertId();
    }

    /**
     * @return int
     */
    protected function getRowsAffected()
    {
        return $this->rowsAffected;
    }

    /**
     * @return int
     */
    protected function getRowsSelected()
    {
        return $this->rowsSelected;
    }

    /**
     * @return void
     */
    protected function startTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * @return void
     */
    protected function commitTransaction()
    {
        $this->db->commit();
    }

    /**
     * @return void
     */
    protected function rollbackTransaction()
    {
        $this->db->rollBack();
    }

    /**
     * @param $string
     * @return string
     */
    protected static function escapeString($string) {

        $l = strlen($string);
        $n = "";

        for($i=0; $i < $l; $i++) {

            $a = $string[$i];

            switch ($a) {
                case '\\':
                    $n .= '\\\\';
                    break;
                case '\'':
                    $n .= '\\\'';
                    break;
                case '"':
                    $n .= '\"';
                    break;
                default:
                    $n .= $a;
            }

        }

        return $n;

    }

    /**
     * @param $string
     * @return string
     */
    protected static function escapeJSON($string) {

        $l = strlen($string);
        $n = "";

        for($i=0; $i < $l; $i++) {

            $a = $string[$i];

            switch ($a) {
                case '\\':
                    $n .= '\\\\';
                    break;
                case '\'':
                    $n .= '\\\'';
                    break;
                default:
                    $n .= $a;
            }

        }

        return $n;

    }

}