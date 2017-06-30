<?php namespace Xdire\Forma\SchemaEntities;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/4/17
 */

class ORMIndexEntity
{

    /**
     * @var string
     */
    public $primary = null;

    /**
     * @var bool
     */
    public $auto = false;

    /**
     * @var int
     */
    public $primaryType = null;

    /**
     * @var ORMTypeEntity[]
     */
    public $secondary = [];

    /**
     * Will create WHERE string for SQL statement
     *
     * @param   array $values : Column data with values assigned:
     *
     *                              Map of <K, V> where:
     *
     *                              K - is a column name as it was defined in persistent storage
     *                              V - is value of that key
     *
     *                          Those values will be compared against available
     *                          index information ORM having and assigned using
     *                          proper formatting
     *
     * @return  string
     */
    public function toWhereString(array $values) {

        $where = "";
        $primaryAdded = false;

        if($this->primary !== null && isset($values[$this->primary])) {
            $where .= "`".$this->primary."`=".$values[$this->primary];
            $primaryAdded = true;
        }

        if(isset($this->secondary[0])) {

            foreach ($this->secondary as $i => $type) {

                if(isset($values[$type->column])) {

                    if ($i > 0 || $primaryAdded)
                        $where .= " AND ";

                    $where .= "`" . $type->column . "`=" . $values[$type->column];

                }

            }

        }

        return $where;

    }

}