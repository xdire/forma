<?php namespace Xdire\Forma\Models\Processing;

use Xdire\Forma\Exceptions\DBOConfigurationException;
use Xdire\Forma\Interfaces\DBOArrangerInterface;
use Xdire\Forma\Processing\SQLModelTransformer;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/6/17
 */

class DBOModelTransformerManager
{
    /**
     * @var DBOArrangerInterface|null
     */
    private static $default = null;
    /**
     * @var DBOArrangerInterface[]
     */
    private static $arrangers = [];
    /**
     * @var bool
     */
    private static $initialized = false;

    /**
     * @param string|null $arrangerName
     * @param string|null $arrangerClass
     */
    public static function init($arrangerName = null, $arrangerClass = null) {

        if($arrangerName !== null && $arrangerClass !== null) {
            self::addArranger($arrangerName, $arrangerClass, true);
        } else {
            self::addArranger("sql", SQLModelTransformer::class, true);
        }
        self::$initialized = true;

    }

    /**
     * @param string    $name
     * @param string    $arrangerClass
     * @param bool      $makeDefault
     */
    public static function addArranger($name, $arrangerClass, $makeDefault = false) {

        $a = new $arrangerClass();

        if($makeDefault) {
            self::$default = $a;
        }

        self::$arrangers[$name] = $a;

    }

    /**
     * @param string|null   $name
     * @return DBOArrangerInterface|null
     * @throws DBOConfigurationException
     */
    public static function getArranger($name = null) {

        if(!self::$initialized) {
            self::init();
        }

        if($name !== null && isset(self::$arrangers[$name])) {

            return self::$arrangers[$name];

        } else if (self::$default !== null) {

            return self::$default;

        }

        throw new DBOConfigurationException(
            "No ORM Arrangers added to DBO Arranger, DBO ORM Configuration failed", 500);

    }

}