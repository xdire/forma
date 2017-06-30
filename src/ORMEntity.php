<?php namespace Xdire\Forma;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/4/17
 */

use Xdire\Forma\Interfaces\ORMEntityInterface;
use Xdire\Forma\Models\ORM\ORMModel;
use Xdire\Forma\Models\ORM\ORMTypes;
use Xdire\Forma\Models\Processing\DBOModelTransformerManager;
use Xdire\Forma\Models\Query\Query;
use Xdire\Forma\SchemaEntities\ORMTypeStructure;

/**
 *  ORM Entity base class.
 *  ------------------------------------------------
 *  Can be extended for capabilities of ORM Object
 *  access to some storage
 *
 *  Class ORMEntity
 *  @package App\System\DB
 */
class ORMEntity extends ORMModel implements ORMEntityInterface
{

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
     *  Flag which will be populated as long DB request got entity
     *  from the persistent storage
     *  ------------------------------------------------
     *  @var bool
     */
    protected $__persists = false;

    /**
     *  Set of fields which is allowed to be updated for entity
     *  ------------------------------------------------
     *  !> This property is not affect DB IO operations, only
     *     straight entity updates from a side
     *
     *  @var string[]
     */
    protected $__allowed = [];

    /**
     *  If any of allowed properties were defined, this flag
     *  will be raised
     *  ------------------------------------------------
     *  @var bool
     */
    protected $__allowedMode = false;

    /**
     *  Can be constructed with data array.
     *  Array keys need to match names of
     *  the properties defined for child object
     *  ------------------------------------------------
     *  ORMEntity constructor.
     *  @param array|null $data
     */
    public function __construct(array $data = null)
    {
        /*
         *  Get Full class namespace name
         */
        $className = get_class($this);

        /*
         *  Check if schema exists
         */
        if(isset(self::$entitySchema[$className]))

            $this->__currentSchema = &self::$entitySchema[$className];

        /*
         *  Create schema if nothing found
         */
        else
            $this->__currentSchema = &self::createSchema($this);

    }

    /**
     *  Will return value of storage field name assigned to entity parameter
     *
     *  @param   string $originStorageFieldName
     *  @return  mixed|null
     */
    public function get($originStorageFieldName)
    {

        if(isset($this->__currentSchema->columnSet[$originStorageFieldName])) {

            foreach ($this->__currentSchema->types as $type) {

                if($type->column === $originStorageFieldName) {
                    return $this->{$type->parameterName};
                }

            }

        }

        return null;

    }

    /**
     * Will set value of storage field name assigned to object (if any can be found)
     *
     * @param string $originStorageFieldName
     * @param mixed $value
     */
    public function set($originStorageFieldName, $value)
    {

        if(isset($this->__currentSchema->columnSet[$originStorageFieldName])) {

            foreach ($this->__currentSchema->types as $type) {

                if($type->column === $originStorageFieldName)
                    $this->{$type->parameterName} = $value;

            }

        }

    }

    /**
     *  Will refresh current child entity with data from the persistent storage
     */
    public function refresh() {

    }

    /**
     *  Will save current child entity data to the persistent storage
     *
     *  @throws \Exception
     *  @return $this|DBEntity
     */
    public function save() {

        if($this->__persists) {
            return $this->update();
        }

        // Get arranger (for compatibility with 5.6 set into separated operation)
        $arranger = DBOModelTransformerManager::getArranger();

        try {

            // Do Save
            if ($id = $arranger::save($this->__currentSchema, self::createCurrentValuesDataMapping($this))) {

                $this->__persists = true;

                // If index was defined — then newly created entity
                // should populate it with fresh DB id
                if ($index = $this->__currentSchema->primary) {

                    $this->{$index->parameterName} = $id;

                }

            }

        } catch (\Exception $e) {

            /*
             *  If saving process encounter duplication error - try to explain it to user
             */
            if($e->getCode() === 409) {

                if ($this->__currentSchema->primary === null && count($this->__currentSchema->secondary) === 0)

                    throw new \Exception(
                        "While saving entity for table: " . $this->__currentSchema->table .
                        " ORM encounter duplication entity error. " .
                        " It looks like you didn't define any" .
                        " @primary @secondary tags for your entity: " . get_class(new static()) . "." .
                        " Original error: ". $e->getMessage(),
                        $e->getCode());

            }
            /*
             *  If any other error happened due saving, include error explanation
             */
            throw new \Exception("While saving entity for table: " . $this->__currentSchema->table .
                " Error entity: ".get_class(new static()).".".
                " Original error: ". $e->getMessage(),
                $e->getCode());

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
        $arranger::update($this->__currentSchema, self::createCurrentValuesDataMapping($this));

        return $this;

    }

    /**
     * Will delete current child entity from persistent storage
     *
     * Id will be unassigned from entity, but data which entity has in memory will be still available until
     * thread will be closed or entity will be grabbed by GC
     *
     * @param   void
     * @return  void
     */
    public function delete()
    {

        if($this->__persists) {

            // Get arranger (for compatibility with 5.6 set into separated operation)
            $arranger = DBOModelTransformerManager::getArranger();

            // Do Save
            if ($arranger::delete($this->__currentSchema, self::createCurrentValuesDataMapping($this))) {

                // Flag entity as not persisted in system
                $this->__persists = false;

                // Erase value of the indexing parameter in the child
                if($this->__currentSchema->primary) {

                    $this->{$this->__currentSchema->primary->parameterName} = null;

                }

            }

        }

    }

    /**
     *  System hidden fromArray method
     *
     *  Will not be doing additional checkups for changes and etc.
     *
     *  Will be adding persistence flag if index exists for entity
     *
     *  No casting will be performed (assuming it was done by other process)
     *
     *  @param   array|null $data
     *  @return  $this | null
     */
    public function __fromArray(array $data = null) {

        $schema = &$this->__currentSchema;

        if ($data === null)
            return null;

        /*
         *  Assign values according to schema mapping
         */
        foreach ($schema->types as $name => $type) {

            if(array_key_exists($type->column, $data)) {

                $this->{$name} = $data[$type->column];

            }

        }
        /*
         *  Make sure that if primary index value was set, then entity persists
         */
        if($index = $schema->getIndex()) {

            if(isset($data[$index->primary])) {

                $this->__persists = true;

            }

        }

        return $this;

    }

    /**
     *  Will return current descendant instance of this model
     *
     *  @param array|null $data
     *  @return static
     */
    public function __getBlank(array $data = null) {

        return new static($data);

    }

    /**
     *  Will make Find ID operation in the persistent storage
     *
     *  @param   $id
     *  @return  static | null
     */
    public static function find($id) {

        $e = new static();

        if($schema = $e->__currentSchema) {

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
        return new Query($e, $e->__currentSchema);

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

    /**
     *  @return void
     */
    public function fromArray()
    {
        // TODO: Implement fromArray() method.
    }

    /**
     *  @return array
     */
    public function toArray()
    {
        $a = [];

        foreach ($this->__currentSchema->types as $item) {

            /*
             *  If property is not some of the dependant properties like
             *  relation or just some custom ORMEntity extended push pull
             *  then just assign value
             */
            if(!$item->withDependant)

                $a[$item->parameterName] = $this->{$item->parameterName};

            /*
             *  If property is a dependant type - meaning some custom type
             *  or simple relation then we should understand how properly
             *  include those into the output array
             */
            else {

                /*
                 *  If it is a relation of one of the defined types
                 *  then we surely can know that we dealing with some
                 *  of predefined ORMEntity extended types here
                 */
                if($item->withRelationType !== 0) {

                    if ($item->withRelationType === 2) {



                    }
                    elseif ($item->withRelationType === 1) {



                    }

                }
                /*
                 *  If no relation was defined then we can try to check what type
                 *  property carrying to include list of items or to just assign value
                 */
                else if(is_array($this->{$item->parameterName})) {

                    $a[$item->parameterName] = [];
                    /**
                     * @var ORMEntity $dependantItem
                     */
                    foreach ($this->{$item->parameterName} as $dependantItem) {

                        $a[$item->parameterName][] = $dependantItem->toArray();

                    }

                } else

                    $a[$item->parameterName] = $this->{$item->parameterName}->toArray();


            }

        }

        return $a;
    }

    /**
     *  @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray(), true);
    }

    /**
     *  Will create array with column associations of current child data
     *
     *  @param   ORMEntity $entity
     *  @return  mixed[]
     */
    private static function createCurrentValuesDataMapping(ORMEntity $entity) {

        $a = [];

        foreach ($entity->__currentSchema->types as $property => $type) {

            /*
             *  Check if allowed values mode is enabled
             */
            if($entity->__allowedMode) {

                /*
                 *  Add value to updating array only if it is allowed to be added
                 */
                if($entity->__allowed[$property])
                    $a[$type->column] = $entity->{$property};

            }
            /*
             *  If no restrictions presented then just add to resulting array
             */
            else {

                $a[$type->column] = $entity->{$property};

            }

        }

        return $a;

    }

    /**
     *  Will create schema according to entity DOC definitions
     *
     *  @param   ORMEntity $e
     *  @return  ORMTypeStructure
     */
    private static function &createSchema(ORMEntity $e) {

        $struct = new ORMTypeStructure();
        $r = new \ReflectionClass($e);

        if($classType = self::__getTypeFromTextAnnotation($r->getDocComment())) {
            $struct->table = $classType->table;
        }

        /*
         *  Roam through entity properties
         */
        foreach ($r->getProperties() as $p) {

            /*
             *  Pass properties which are prefixed with _
             *  (meaning system records or private fields)
             */
            if(isset($p->name[1])) {
                if($p->name[0] === '_')
                    continue;
            }

            /*
             *  Check if some type was created
             */
            if($typeCreated = self::__getTypeFromTextAnnotation($p->getDocComment())) {

                if($typeCreated->ignore)
                    continue;

                $typeCreated->parameterName = $p->name;
                $struct->types[$p->name] = $typeCreated;

                /*
                 *  Assign primary key
                 */
                if($typeCreated->primary === true) {
                    $struct->primary = $typeCreated;
                }
                /*
                 *  Assign one of the secondary keys
                 */
                else if ($typeCreated->secondary === true) {
                    $struct->secondary[] = $typeCreated;
                }
                /*
                 *  If type was undefined - set type as T_STR or T_NULL
                 */
                if($typeCreated->type === 0) {
                    $typeCreated->type = ORMTypes::T_STR|ORMTypes::T_NULL;
                }
                /*
                 *  Assign default column name if nothing defined in description
                 */
                if($typeCreated->column === null)
                    $typeCreated->column = $p->name;

            }
            /*
             *  If nothing was created — assign default type
             */
            else {

                $struct->types[$p->name] = self::__getDefaultTypeForName($p->name);

            }

        }

        $struct->createIndex();
        $struct->createColumnTypes();

        /*
         *  Assign freshly created schema to schema MAP storage
         */
        self::$entitySchema[$r->name] = $struct;

        return $struct;

    }

}