<?php namespace Xdire\Forma\Processing;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/6/17
 */

use Xdire\Forma\Interfaces\DBOArrangerInterface;
use Xdire\Forma\Models\Connector\DBO;
use Xdire\Forma\Models\DBOConfiguration;
use Xdire\Forma\SchemaEntities\ORMTypeStructure;

class SQLModelTransformer extends DBO implements DBOArrangerInterface
{

    /**
     * @var SQLModelTransformer | null
     */
    protected static $instance = null;

    const T_BOOL = 1;
    const T_INT = 2;
    const T_STR = 3;
    const T_FLOAT = 4;
    const T_ARRAY = 5;
    const T_NULL = 64;

    public static function init(DBOConfiguration $conf = null) {
        self::$instance = new self($conf);
    }

    function __construct(DBOConfiguration $conf = null) {
        parent::__construct($conf);
    }

    /**
     * @param ORMTypeStructure $structure
     * @param array            $valueArray
     * @return mixed
     */
    public static function getById(ORMTypeStructure $structure, array $valueArray)
    {
        
        if(self::$instance === null)
            self::init();

        $r = self::$instance->read("SELECT * FROM `".$structure->table."` WHERE ".
            $structure->getIndex()->toWhereString($valueArray));

        return self::_fetchOne($r, $structure->columnTypes);

    }

    /**
     * @param                  $statement
     * @param ORMTypeStructure $structure
     * @return mixed
     */
    public static function getRawSingle($statement, ORMTypeStructure $structure)
    {

        if(self::$instance === null)
            self::init();

        $r = self::$instance->read($statement);

        return self::_fetchOne($r, $structure->columnTypes);

    }

    /**
     * @param                  $statement
     * @param ORMTypeStructure $structure
     * @return mixed
     */
    public static function getRawMultiple($statement, ORMTypeStructure $structure)
    {
        if(self::$instance === null)
            self::init();

        $r = self::$instance->read($statement);

        return self::_fetchMany($r, $structure->columnTypes);

    }

    /**
     * Will save item represented by provided structure
     *
     * Exception will be thrown if something will go out of normal order of execution
     *
     * @param   ORMTypeStructure $structure
     * @param   array            $valueArray : Value array data is a Map of <K,V> where
     *
     *                                          K -> is a column name
     *                                          V -> column value
     *
     * @return  int                         : primary key which was assigned to newly created
     *                                      entity
     *
     * @throws \Exception
     */
    public static function save(ORMTypeStructure $structure, array $valueArray)
    {
        if(self::$instance === null)
            self::init();

        /*
         *  If no table was defined reject operation and deliver error message with failure cause
         */
        if($structure->table === null)
            throw new \Exception(
                "Table name in [@table [table_name]] tag for ORM is not defined\n".
                " for schema with following column-set:\n ".(var_export($structure->columnSet, true)." \n".
                    " ORM can't proceed with saving data."),
                400);

        $savingArray = [];

        foreach ($valueArray as $name => $value) {

            /*
             *  Check that only proper set of fields get into action
             */
            if(!isset($structure->columnSet[$name]))
                continue;

            $savingArray["`".$name."`"] = self::_typeCollate($structure->columnTypes, $name, $value);

        }

        $fields = array_keys($savingArray);

        self::$instance->write("INSERT INTO `".($structure->table)."` ".
            "(".implode(",",$fields).") VALUES (".implode(",",$savingArray).")");

        /*
         *  If structure index is integer then case last ID as integer
         */
        if(isset($structure->primary) & $structure->primary->type === self::T_INT)
            return (int) self::$instance->getLastInsertId();
        else
            return self::$instance->getLastInsertId();

    }

    /**
     * Will update item in persistent table represented with provided structure
     *
     * Exception will be thrown if something will go out of normal order of execution
     *
     * @param   ORMTypeStructure $structure
     *
     * @param   array            $valueArray : Value array data is a Map of <K,V> where
     *
     *                                          K -> is a column name
     *                                          V -> column value
     *
     *                                       As well it can be only partial data submitted
     *                                       through that method, so if you making partial
     *                                       update submit, be sure to pass full data for
     *                                       index propagation through $indexData parameter
     *
     *
     * @param   array | null     $indexData : Index data is a Map of <K,V> where
     *
     *                                          K -> is a column name
     *                                          V -> column value
     *
     *                                      If you not passing actual primary index data
     *                                      through value array parameter, then pass
     *                                      data with values for primary|secondary through
     *                                      that parameter
     *
     * @return  int
     * @throws \Exception
     */
    public static function update(ORMTypeStructure $structure, array $valueArray, array $indexData = null)
    {

        if(count($valueArray) === 0)
            return 0;

        if(self::$instance === null)
            self::init();

        /*
         *  If no table was defined reject operation and deliver error message with failure cause
         */
        if($structure->table === null)
            throw new \Exception(
                "Table name in [@table [table_name]] tag for ORM is not defined\n".
                " for schema with following column-set:\n ".(var_export($structure->columnSet, true)." \n".
                    " ORM can't proceed with updating data."),
                400);

        /*
         *  If no index defined reject operation and deliver error message with failure cause
         */
        if($structure->primary === null && !isset($structure->secondary[0]))
            throw new \Exception(
                "Index tags [@primary] | [@secondary] is not defined for entity ".
                " representing table: ".$structure->table.
                " can't proceed with data update",
                400);

        $setData = "";
        $i = 0;

        foreach ($valueArray as $name => &$value) {

            /*
             *  Check that only proper field get into DB query
             */
            if(!isset($structure->columnSet[$name]))
                continue;

            $value = self::_typeCollate($structure->columnTypes, $name, $value);

            $setData .= $name."=".$value.",";
            $i++;

        }

        if($i === 0)
            return 0;

        self::$instance->write("UPDATE `".($structure->table).
            "` SET ".rtrim($setData,',').
            " WHERE ".($structure->getIndex()->toWhereString($indexData !== null ? $indexData : $valueArray)));

        return self::$instance->rowsAffected;

    }

    /**
     * Will delete item in persistent table represented with provided structure
     *
     * @param   ORMTypeStructure    $structure
     * @param   mixed[]             $valueArray : Index data is a Map of <K,V> where
     *
     *                                          K -> is a column name
     *                                          V -> column value
     *
     *                                          Primary data with index information
     *                                          should be passed through this parameter
     *
     * @return  int
     * @throws  \Exception
     */
    public static function delete(ORMTypeStructure $structure, array $valueArray)
    {

        if(self::$instance === null)
            self::init();

        /*
         *  If no table was defined reject operation and deliver error message with failure cause
         */
        if($structure->table === null)
            throw new \Exception(
                "Table name in [@table [table_name]] tag for ORM is not defined\n".
                " for schema with following column-set:\n ".(var_export($structure->columnSet, true)." \n".
                    " ORM can't proceed with deleting data."),
                400);

        /*
         *  If no index defined reject operation and deliver error message with failure cause
         */
        if($structure->primary === null && !isset($structure->secondary[0]))
            throw new \Exception(
                "Index tags [@primary] | [@secondary] is not defined for entity ".
                " representing table: ".$structure->table.
                " can't proceed with data deletion",
                400);

        self::$instance->write("DELETE FROM `".($structure->table).
            "` WHERE ".($structure->getIndex()->toWhereString($valueArray)));

        return self::$instance->rowsAffected;

    }

    /**
     * @return mixed
     */
    public function getDBInstance()
    {
        if(self::$instance === null)
            self::init();
        return self::$instance;
    }

    /**
     *
     */
    public function startTransaction()
    {
        parent::startTransaction();
    }

    /**
     *
     */
    public function commitTransaction()
    {
        parent::commitTransaction();
    }

    /**
     *
     */
    public function rollbackTransaction()
    {
        parent::rollbackTransaction();
    }

    /**
     * @param   string $string
     * @return  string
     */
    public static function escapeString($string)
    {
        return parent::escapeString($string);
    }

    /**
     * @param   string $string
     * @return  string
     */
    public static function escapeJSON($string)
    {
        return parent::escapeJSON($string);
    }


    /**
     * Will return result or null if nothing fetched
     *
     * @param                   $statement
     * @param   int[]           $castTable
     * @return  mixed[] | null
     */
    private static function _fetchOne($statement, &$castTable) {

        if ($row = self::fetchRow($statement)) {

            self::_casting($row, $castTable);

            return $row;

        }

        return null;

    }

    /**
     * Will return result or empty array if nothing fetched
     *
     * @param $statement
     * @param $castTable
     * @return mixed[][]
     */
    private static function _fetchMany($statement, &$castTable) {

        $a = [];
        $plan = null;

        /*
         *  First row extracted will be creating casting plan
         */
        if($row = self::fetchRow($statement)) {
            $plan = self::_prepareCasting($row, $castTable);
            self::_castingByPlan($row, $plan);
            $a[] = $row;
        }
        /*
         *  Rest rows will be casted according the plan
         */
        while ($row = self::fetchRow($statement)) {

            $i = 0;

            foreach ($row as &$value) {

                if($value !== null) {

                    switch ($plan[$i]) {

                        case 5: break;
                        case 1: $value = (int) $value; break;
                        case 2: $value = (int) $value === 1 ? true : false; break;
                        case 3: $value = (float) $value; break;
                        case 4: $value = json_decode($value); break;
                        default: break;

                    }

                }

                $i++;

            }

            $a[] = $row;

        }

        return $a;

    }

    /**
     * Will reformat value accordingly to SQL database rules
     *
     * @param array $types
     * @param       $key
     * @param       $value
     * @return float|int|string
     */
    private static function _typeCollate(array &$types, $key, $value) {

        if(isset($types[$key])) {

            $type = $types[$key];

            /*
             *  Check if value can be NULL and has actual NULL value
             *  -> return NULL if that combination allowed
             */
            if(($type & self::T_NULL) !== 0 && $value === null)
                return "NULL";
            /*
             *  Bring back TYPE if flag is possibly may be NULL
             *  -> will transform TYPE back to proper type
             */
            elseif (($type & self::T_NULL) !== 0)
                $type = $type ^ self::T_NULL;

            /*
             *  Do type aligning
             *  -> will transform value to properly formatted typed value
             */
            switch ($type) {

                case static::T_INT :
                    $value = (int)$value;
                    break;

                case static::T_FLOAT :
                    $value = (float)$value;
                    break;

                case static::T_BOOL :
                    $value = $value ? 1 : 0;
                    break;

                case static::T_STR :
                    $value = "'" . self::$instance->escapeString((string)$value) . "'";
                    break;

                case static::T_ARRAY :
                    $value = self::$instance->escapeJSON(json_encode($value));
                    break;

                default:
                    break;

            }

        }
        /*
         *  If no specific types were provided, then value will be
         *  safely stringified
         */
        else
            $value = "'" . self::$instance->escapeString((string)$value) . "'";


        return $value;

    }

    /**
     * Will prepare casting plan.
     *
     * Main usage is for fetch multiple entities according
     * to fast plan structure which accessible with lesser
     * amount of operations
     *
     * @param   mixed[] $row
     * @param           $castTable
     * @return  int[] : Map of <Int, Int> Where <K> is row number, <V> Code of type assigned
     */
    private static function _prepareCasting(array &$row, &$castTable) {

        $castingPlan = [];
        $i = 0;

        foreach ($row as $name => $value) {

            if(isset($castTable[$name])) {

                $cast = $castTable[$name];

                if (($cast & self::T_NULL) !== 0)
                    $cast = $cast ^ self::T_NULL;

                switch ($cast) {

                    case static::T_INT :
                        $castingPlan[$i] = 1;
                        break;

                    case static::T_BOOL:
                        $castingPlan[$i] = 2;
                        break;

                    case static::T_FLOAT:
                        $castingPlan[$i] = 3;
                        break;

                    case static::T_ARRAY:
                        $castingPlan[$i] = 4;
                        break;

                    default:
                        $castingPlan[$i] = 5;
                        break;

                }

            } else
                $castingPlan[$i] = 5;

            $i++;

        }

        return $castingPlan;

    }


    /**
     *  Will cast all values properly as they defined by cast parameter in Model
     *
     *  @param array $row
     *  @param int[] $castTable
     */
    private static function _casting(array &$row, &$castTable) {

        foreach ($castTable as $name => $cast) {

            if(isset($row[$name])) {

                /*
                 *  Bring back TYPE if flag is possibly may be NULL
                 *  -> will transform TYPE back to proper type
                 */
                if (($cast & self::T_NULL) !== 0)
                    $cast = $cast ^ self::T_NULL;

                switch ($cast) {

                    case static::T_INT :
                        $row[$name] = (int)$row[$name];
                        break;

                    case static::T_BOOL:
                        $row[$name] = (int)$row[$name] === 1 ? true : false;
                        break;

                    case static::T_FLOAT:
                        $row[$name] = (float)$row[$name];
                        break;

                    case static::T_ARRAY:
                        $row[$name] = json_decode($row[$name], true);
                        break;

                    default: break;

                }

            }

        }

    }

    /**
     *  Will do casting by Plan defined for casting
     *
     *  @param array $row
     *  @param \Closure[] $planCastTable
     */
    private static function _castingByPlan(array &$row, array &$planCastTable) {

        $i = 0;

        foreach ($row as &$value) {

            if($value !== null) {

                switch ($planCastTable[$i]) {
                    case 5: break;
                    case 1: $value = (int) $value; break;
                    case 2: $value = (int) $value === 1? true:false; break;
                    case 3: $value = (float) $value; break;
                    case 4: $value = json_decode($value); break;
                    default: break;
                }

            }

            $i++;

        }

    }

}