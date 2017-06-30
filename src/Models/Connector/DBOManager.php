<?php namespace Xdire\Forma\Models\Connector;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 2/16/17
 */

final class DBOManager
{

    /**
     * @var \PDO[]
     */
    private static $instances = [];

    /**
     * @param string $address
     * @param int $port
     * @param string $dbname
     * @return \PDO | null
     */
    public static final function &getDbInstance($address, $port, $dbname) {

        $key = self::hash($address, $port, $dbname);
        $ret = null;

        // Return instance if one found
        if(isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        // Return instance if any defined (will get last one)
        if(count(self::$instances) > 0) {
            $instance = end(self::$instances);
            return $instance;
        }

        // Null if not initialized
        return $ret;

    }

    /**
     * @param string $address
     * @param int $port
     * @param string $dbname
     * @param \PDO $connection
     */
    public static final function addInstance($address, $port, $dbname, \PDO &$connection) {

        $key = self::hash($address, $port, $dbname);

        self::$instances[$key] = $connection;

    }

    /**
     * Provide hashed integer from set of parameters
     *
     * @param $address
     * @param $port
     * @param $dbname
     * @return int
     */
    private static final function hash($address, $port, $dbname) {

        $addrLength = strlen($address);
        $dbnmLength = strlen($dbname);

        $total = $aInt = $dInt = 0;

        for($i = 0; $i < $addrLength; $i++, $aInt += ord($address[$i-1]));
        for($i = 0; $i < $dbnmLength; $i++, $dInt += ord($dbname[$i-1]));

        $total = $total | $port;

        $total = ($total << 20) | $aInt;

        $total = ($total << 10) | $dInt;

        return $total;

    }

}