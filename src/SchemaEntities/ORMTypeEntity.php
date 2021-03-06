<?php namespace Xdire\Forma\SchemaEntities;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/4/17
 */

class ORMTypeEntity
{

    /**
     *  Assigned entity field
     *  @var string|null
     */
    public $parameterName = null;

    /**
     *  Table assigned to type (will be usable only for class declaration)
     *  @var string|null
     */
    public $table = null;

    /**
     *  If this is a primary key element
     *  @var bool
     */
    public $primary = false;

    /**
     *  If this a secondary key element
     *  @var bool
     */
    public $secondary = false;

    /**
     *  If value autogenerated and shouldn't be saved or updated
     *  @var bool
     */
    public $auto = false;

    /**
     *  What type it should be casted
     *  @var int
     */
    public $type = 0;

    /**
     *  What column it should address to pull data
     *  @var string|null
     */
    public $column = null;

    /**
     *  Is it having default value attached
     *  @var bool
     */
    public $withDefault = false;

    /**
     *  Default value attached (anyway depends on a flag above)
     *  @var string|int|float|null
     */
    public $default = null;

    /**
     *  Column will not be available for saving
     *  @var bool
     */
    public $readOnly = false;

    /**
     *  That Mapping will be excluded from ORM interaction
     *  @var bool
     */
    public $ignore = false;

    /**
     *  Will define relationship Mapping of dependant class
     *  @var string|null
     */
    public $withRelation = null;

    /**
     *  Contains relation type or null, relation type can be enumerate of  1 | 2
     *  @var int
     */
    public $withRelationType = 0;

    /**
     *  Marker to signal that this type property is dependant type and should be transformed as dependant
     *  @var bool
     */
    public $withDependant = false;

}