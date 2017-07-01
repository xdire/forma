<?php namespace Xdire\Forma;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 2/16/17
 */

use Xdire\Forma\Interfaces\ORMEntityInterface;

use Xdire\Forma\Models\ORM\ORMModel;
use Xdire\Forma\Models\ORM\ORMTypes;
use Xdire\Forma\Models\Processing\DBOModelTransformerManager;
use Xdire\Forma\Models\Query\Query;

use Xdire\Forma\SchemaEntities\ORMTypeEntity;
use Xdire\Forma\SchemaEntities\ORMTypeStructure;

class DBEntity extends ORMModel implements ORMEntityInterface
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

    function __construct(array $data = null)
    {

        $this->fromArray($data);

    }
    /**
     *  List of available schema for entities,
     *  will do caching of currently involved structures
     *  ------------------------------------------------
     *  @var ORMTypeStructure[]
     */
    private static $entitySchema = [];
    /**
     *  Instance of current schema for entity
     *  ------------------------------------------------
     *  @var ORMTypeStructure|null
     */
    protected $__currentSchema = null;
    /**
     *  OPTIONAL:
     *  !> if your table index is hybrid and consists from two or more columns
     *
     *  Define at your instance of model this parameter as following:
     *
     *  ["some_field", "another_field"]
     *
     *  Please note: your Entity data need to have those fields defined
     *  in currentData property
     *
     *  @var null | array
     */
    protected $idFieldComplex = null;
    /**
     *  REQUIRED if no other type of id index was set
     *
     *  Define at your instance of model this parameter as [string]
     *
     *  Need to follow id field column name in your exact DB table
     *
     *  @var null | int | string
     */
    protected $idField = null;
    /**
     *  OPTIONAL
     *
     *  Define id index field type if it not a [string] type
     *
     *  Can be skipped, then ORM will stringify this parameter
     *
     *  @var null | int
     */
    protected $idFieldType = null;
    /**
     *  REQUIRED
     *
     *  For CRUD capabilities tableName should be defined as well
     *  as idField or idFieldComplex parameters
     *
     *  @var null | string
     */
    protected $tableName = null;

    /**
     *  OPTIONAL
     *
     *  Fields which can be saved with a model
     *
     *  Please Note: if that property is not filled in, and your model have
     *  some bogus data, that will cause DBO error on data save process, while
     *  this parameter filled can help ORM to filter your model data properly
     *
     *  @var string[]
     */
    protected $fields = [];

    /**
     *  HIDDEN LAZY
     *
     *  Model will transform fields into fieldSet at the save/update stage
     *  @var bool[]
     */
    protected $__fieldSet = null;

    /**
     *  OPTIONAL
     *
     *  Provide casting data with that property to manage typing of your data
     *  according to some rules, example:
     *
     *  protected static $casts = [
     *       "id" => self::T_INT,
     *       "column" => self::T_INT | self::T_NULL,
     *       "condition" => self::T_BOOL | self::T_NULL,
     *       "price" => self::T_FLOAT | self::T_NULL,
     *  ];
     *
     * @var int[]
     */
    protected $casts = [];

    /**
     *  Provide default values for fields, if some fields will be not having data
     *  at the save stage, defaults will be applied
     *
     *  @var mixed[]
     */
    protected $defaults = [];

    /**
     * Map with changes.
     * Will be filled on set methods
     * Will be cleaned on entity save / update
     *
     * @var mixed[]
     */
    protected $changesData = [];

    /**
     * Current data attached to entity
     * Will be changed as the entity setters executed
     *
     * @var mixed[]
     */
    protected $currentData = [];

    /**
     *  Flag which will be populated as long DB request got entity
     *  from the persistent storage
     *
     *  @var bool
     */
    protected $persists = false;

    /**
     *  Set of fields which is allowed to be updated for entity
     *  !> This property is not affect DB IO operations, only
     *     straight entity updates from a side
     *
     * @var string[]
     */
    protected $allowed = [];

    /**
     *  If any of allowed properties were defined, this flag
     *  will be raised
     *
     *  @var bool
     */
    private $allowedMode = false;

    /*  ----------------------------------------------------------------
     *
     *
     *                          PROTECTED METHODS
     *
     *
     *  ----------------------------------------------------------------
     */

    /**
     *  Method set as protected by the matter left the decision to Model
     *  Developer on how to handle GETTERS and SETTERS and their level
     *  of visibility.
     *
     *  Will set property to provided value applying following check-ups:
     *
     *  1) Check if property is allowed to be changed or set
     *  2) Property will be casted to designated format from a cast table
     *  3) Changes data will be populated if value is differs from existed
     *
     *  @param $field
     *  @param $value
     */
    protected function set($field, $value) {

        if($this->allowedMode) {
            if(!isset($this->allowed[$field]))
                return;
        }

        if(array_key_exists($field, $this->currentData)) {

            $cVal = $this->_compareCasting($field,$value);

            $v = $this->currentData[$field];
            $this->currentData[$field] = $cVal;

            if($v !== $cVal)
                $this->changesData[$field] = $cVal;

            return;

        }

        $this->currentData[$field] = $value;
        $this->changesData[$field] = $value;

        return;

    }

    /**
     *  Public method allows to get value for identified Storage column
     *
     *  Will return property if it's existed, NULL will be returned if not
     *
     *  @param $field
     *  @return mixed|null
     */
    public function get($field) {

        if(array_key_exists($field, $this->currentData)) {
            return $this->currentData[$field];
        }

        return null;

    }

    /**
     * System hidden fromArray method
     *
     * Will not be doing additional checkups for changes and etc.
     *
     * Will be adding persistence flag if index exists for entity
     *
     * No casting will be performed
     *
     * @param array|null $data
     * @return $this|null
     */
    public function __fromArray(array $data = null) {

        if ($data !== null) {

            $this->currentData = $data;

            if (isset($this->currentData[$this->idField]))
                $this->persists = true;

            return $this;

        }

        return null;

    }

    /**
     *  Will return current child instance of this model
     *
     *  @param array|null $data
     *  @return static
     */
    public function __getBlank(array $data = null) {

        return new static($data);

    }

    /*  ----------------------------------------------------------------
     *
     *
     *                        PUBLIC CRUD+ METHODS
     *
     *
     *  ----------------------------------------------------------------
     */

    /**
     *  Will refresh current child entity with data from the persistent storage
     */
    public function refresh() {

    }

    /**
     *  Will save current child entity data to the persistent storage
     *
     *  @return $this|DBEntity
     */
    public function save() {

        if($this->persists) {
            return $this->update();
        }

        // Get arranger (for compatibility with 5.6 set into separated operation)
        $arranger = DBOModelTransformerManager::getArranger();

        // Do Save
        if($id = $arranger::save($this->_getStructure(), $this->currentData)) {

            $this->persists = true;

            // If index was defined — then newly created entity
            // should populate it with fresh DB id
            if($index = $this->__currentSchema->primary) {

                $this->set($index->parameterName, $id);

            }

        }

        return $this;

    }

    /**
     *  Will update current child entity changed data (if any) to the persistent storage
     *
     *  @return $this
     */
    public function update() {

        // Get arranger (for compatibility with 5.6 set into separated operation)
        $arranger = DBOModelTransformerManager::getArranger();

        // Do Update
        $arranger::update($this->_getStructure(), $this->changesData, $this->currentData);

        $this->changesData = [];

        return $this;

    }

    /**
     *  Will delete current child entity
     */
    public function delete() {

        if($this->persists) {

            // Get arranger (for compatibility with 5.6 set into separated operation)
            $arranger = DBOModelTransformerManager::getArranger();

            // Do Delete
            if ($arranger::delete($this->_getStructure(), $this->currentData)) {

                // Flag entity as not persisted in system
                $this->persists = false;

                // Erase value of the indexing parameter in the child
                if($this->__currentSchema->primary) {

                    $this->currentData[$this->__currentSchema->primary->parameterName] = null;

                }

            }

        }

    }

    /**
     *  Will set one or set of the columns to be allowed to be passed through setter
     *
     *  @param string[] $thoseFields
     */
    public function allowOnly(array $thoseFields) {

        $this->allowedMode = true;
        foreach ($thoseFields as $field)
            $this->allowed[$field] = true;

    }

    /*  ----------------------------------------------------------------
     *
     *
     *                        PUBLIC METHODS
     *
     *
     *  ----------------------------------------------------------------
     */

    /**
     * Will return Key => Value Map of Changes which Entity having
     * at current point of time.
     *
     * Changes will be erased as the entity successfully updates it's
     * state in the DB
     *
     * @return \mixed[]
     */
    public function getChangesData() {
        return $this->changesData;
    }

    /**
     * Will return Key => Value Map of Current Data which Entity having
     * at current point of time.
     *
     * @return \mixed[]
     */
    public function getCurrentData() {
        return $this->currentData;
    }

    /**
     * Will be filling Entity with data according to defined rules.
     *
     * If some changes to existing data will be found - they will
     * be accounted as changes.
     *
     *
     * @param array|null $array
     * @return $this
     */
    public function fromArray(array $array = null) {

        if($array !== null) {

            foreach ($array as $key => $value) {

                $this->set($key, $this->_compareCasting($key, $value));

            }

        }

        return $this;

    }

    /**
     * @param  string $json
     * @return DBEntity|null
     */
    public function fromJSON($json) {

        if($e = json_decode($json, true)) {
            return $this->fromArray($e);
        }

        return null;

    }

    /**
     * @return \mixed[]
     */
    public function toArray() {

        return $this->currentData;

    }

    /**
     * @return string
     */
    public function toJSON() {

        return json_encode($this->currentData);

    }

    /*  ----------------------------------------------------------------
     *
     *
     *                      PUBLIC STATIC METHODS
     *
     *
     *  ----------------------------------------------------------------
     */

    /**
     *  Will make Find ID operation in the persistent storage
     *
     * @param   $id
     * @return  static | null
     */
    public static function find($id) {

        $e = new static();

        if($schema = $e->_getStructure()) {

            // Get arranger (for compatibility with 5.6 set into separated operation)
            $arranger = DBOModelTransformerManager::getArranger();

            // Get results from DB Arranger
            return $e->__fromArray($arranger::getById(
                $e->__currentSchema, [$e->__currentSchema->primary->column => $id]));

        }

        return null;

    }

    /**
     *  Will provide query object to make AR or RAW query
     *
     *  @return Query
     */
    public static function query() {

        $e = new static();
        return new Query($e, $e->_getStructure());

    }

    public static function startTransaction() {
        DBOModelTransformerManager::getArranger()->startTransaction();
    }

    public static function commitTransaction() {
        DBOModelTransformerManager::getArranger()->commitTransaction();
    }

    public static function rollbackTransaction() {
        DBOModelTransformerManager::getArranger()->rollbackTransaction();
    }

    public static function escapeString($string) {
        $a = DBOModelTransformerManager::getArranger();
        return $a::escapeString($string);
    }

    /*  ----------------------------------------------------------------
     *
     *
     *                        PRIVATE HELPERS
     *
     *
     *  ----------------------------------------------------------------
     */

    private function _compareCasting($key, $value) {

        if(isset($this->casts[$key])) {

            switch ($this->casts[$key]) {

                case (ORMTypes::T_INT) :
                    $value = (int)$value;
                    break;

                case (ORMTypes::T_BOOL) :
                    $value = ($value === true || (int)$value === 1 || $value === "true") ? true : false;
                    break;

                case (ORMTypes::T_FLOAT):
                    $value = (float)$value;
                    break;

                case (ORMTypes::T_ARRAY):
                    $value = json_decode($value, true);
                    break;

                default:  break;

            }

        }

        return $value;

    }

    /**
     * @return ORMTypeStructure
     */
    private function _getStructure() {

        if($this->__currentSchema !== null)
            return $this->__currentSchema;

        $name = get_class($this);

        if(isset(self::$entitySchema[$name])) {

            $this->__currentSchema = &self::$entitySchema[$name];

        } else {

            $this->__currentSchema = &self::createSchema($this);

        }

        return $this->__currentSchema;

    }

    /**
     *  Will create schema according to entity definition
     *
     *  @param   DBEntity $e
     *  @return  ORMTypeStructure
     */
    private static function &createSchema(DBEntity $e) {

        $struct = new ORMTypeStructure();

        $struct->table = $e->tableName;

        /**
         *  Define primary index
         */
        if(isset($e->idField)) {

            $eType = new ORMTypeEntity();
            $eType->primary = true;
            $eType->auto = true;
            $eType->column = $e->idField;
            $eType->parameterName = $e->idField;
            $eType->type = $e->idFieldType !== null ? $e->idFieldType : ORMTypes::T_STR;

            /*
             *  Add primary index as first field and make it primary
             */
            $struct->types[$eType->parameterName] = $eType;
            $struct->primary = $eType;

        }

        foreach ($e->fields as $field) {

            /*
             *  Check that field which we looking at is not an ID field
             *  (we don't want to overwrite primary ID type)
             */
            if($field === $e->idField)
                continue;

            $eType = new ORMTypeEntity();

            /*
             *  For DBEntity - parameter and column will be the same
             */
            $eType->parameterName = $field;
            $eType->column = $field;

            /*
             *  Check casts available for entity
             */
            if(isset($e->casts[$field])) {

                /*
                 *  Apply cast if one found
                 */
                $eType->type = $e->casts[$field];

            } else {

                /*
                 *  Apply string | null cast if no cast defined for field
                 */
                $eType->type = ORMTypes::T_STR | ORMTypes::T_NULL;

            }

            /*
             *  If some of Defaults were defined - apply them
             */
            if(isset($e->defaults[$field])) {
                $eType->withDefault = true;
                $eType->default = $e->defaults[$field];
            }

            $struct->types[$field] = $eType;

        }

        $struct->createIndex();
        $struct->createColumnTypes();

        return $struct;

    }

}