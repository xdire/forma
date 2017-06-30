<?php namespace Xdire\Forma\Models\ORM;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/4/17
 *
 * For compatibility with PHP 5.6 & PHP 7.0.x where scope of constants cant be limited to
 * private (as like in PHP 7.1) — we using this static structure to hold and maintain
 * ORM types
 *
 * Class ORMTypes
 * @package App\System\DB\Models\ORM
 */
class ORMTypes
{

    /**
     *  List of available types for application (static)
     *  ------------------------------------------------
     */
    const T_BOOL = 1;
    const T_INT = 2;
    const T_STR = 3;
    const T_FLOAT = 4;
    const T_ARRAY = 5;
    const T_NULL = 64;

    private static $types = [
        "bool" => 1, "boolean" => 1, "BOOL" => 1,
        "int" => 2, "integer" => 2, "INT" => 2,
        "str" => 3, "string" => 3, "STR" => 3,
        "float" => 4, "double" => 4, "FLOAT" => 4, "DOUBLE" => 4,
        "array" => 5, "list" => 5, "json" => 5, "JSON" => 5,
        "null" => 64, "NULL"
    ];

    public static function getStringTypeMap() {
        return self::$types;
    }

    public static function getRealTypeForString($string) {
        if(isset(self::$types[$string])) {
            return self::$types[$string];
        }
        return null;
    }

}