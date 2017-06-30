<?php namespace Xdire\Forma\SchemaEntities;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/4/17
 */

class ORMTypeStructure
{
    /**
     * @var ORMIndexEntity
     */
    private $index = null;

    /**
     * @var ORMTypeEntity | null
     */
    public $primary = null;

    /**
     * @var ORMTypeEntity[]
     */
    public $secondary = [];

    /**
     * @var string|null
     */
    public $table = null;

    /**
     * @var ORMTypeEntity[]
     */
    public $types = [];

    /**
     * @var int[]
     */
    public $columnTypes = [];

    /**
     * @var bool[]
     */
    public $columnSet = [];

    /**
     * @return ORMIndexEntity
     */
    public function getIndex() {

        return $this->index;

    }

    /**
     * @return ORMIndexEntity
     */
    public function createIndex() {

        $this->index = new ORMIndexEntity();

        if($this->primary !== null) {
            $this->index->primary = $this->primary->column;
            $this->index->primaryType = $this->primary->type;
            $this->index->auto = $this->primary->auto;
        }

        if(isset($this->secondary[0])) {

            foreach ($this->secondary as $index) {
                $this->index->secondary[] = $index;
            }

        }

        return $this->index;

    }

    /**
     *  Prepare column types information
     */
    public function createColumnTypes() {

        /*
         *  Build type and column indexes
         */
        foreach ($this->types as $type) {

            /*
             *  Check if column defined for that type
             */
            if($type->column !== null) {

                $this->columnTypes[$type->column] = $type->type;

                /*
                 *  Exclude read-only columns from be able to be saved or updated
                 */
                if(!$type->readOnly)
                    $this->columnSet[$type->column] = true;

            }

        }

        /*
         *  Check if primary index is possibly AUTO and it shouldn't be in the Save/Update process
         */
        if($this->primary !== null) {

            /*
             *  Remove main AUTO index from set of fields which can be saved or updated
             */
            if($this->primary->auto && isset($this->columnSet[$this->primary->column])) {

                $this->columnSet[$this->primary->column] = null;

            }

        }

    }

}