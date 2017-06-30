<?php namespace Xdire\Forma\Models\Query\Exec;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 2/16/17
 */

use Xdire\Forma\Models\Processing\DBOModelTransformerManager;
use Xdire\Forma\Models\Query\Query;

use Xdire\Forma\ORMEntity;

class RawQueryExecutor
{

    private $statement = null;
    private $query = null;

    /**
     * RawQueryExecutor constructor.
     * @param string $statement
     * @param Query  $q
     */
    function __construct($statement, Query $q)
    {
        $this->statement = $statement;
        $this->query = $q;
    }

    /**
     * @return mixed|null
     */
    public function getValue() {

        // Get arranger (for compatibility with 5.6 set into separated operation)
        $arranger = DBOModelTransformerManager::getArranger();

        if($data = $arranger::getRawSingle($this->statement, $this->query->__getAttachedStructure())) {

            foreach ($data as $d)
                return $d;

        }

        return null;

    }

    /**
     * @throws \Exception
     * @return ORMEntity|mixed|null
     */
    public function getOne() {

        // Get arranger (for compatibility with 5.6 set into separated operation)
        $arranger = DBOModelTransformerManager::getArranger();

        try {

            if ($data = $arranger::getRawSingle($this->statement, $this->query->__getAttachedStructure())) {

                $e = $this->query->__getAttachedEntity();

                return $e->__getBlank()->__fromArray($data);

            }

        } catch (\Exception $e) {

            throw new \Exception("ORM Raw data read encounter error for query: \n". $this->statement.
                "\nYou should check method which forms this query for proper assigning all".
                " variables into the SQL statement.\nOriginal error: ".$e->getMessage(),
                $e->getCode());

        }

        return null;

    }

    /**
     * @throws \Exception
     * @return ORMEntity[]|mixed[]
     */
    public function getMany() {

        // Get arranger (for compatibility with 5.6 set into separated operation)
        $arranger = DBOModelTransformerManager::getArranger();

        try {

            $data = $arranger::getRawMultiple($this->statement, $this->query->__getAttachedStructure());

        } catch (\Exception $e) {

            throw new \Exception("ORM Raw data read encounter error for query: \n". $this->statement.
                "\nYou should check method which forms this query for proper assigning all".
                " variables into the SQL statement.\nOriginal error: ".$e->getMessage(),
                $e->getCode());

        }

        $e = $this->query->__getAttachedEntity();

        $a = [];

        foreach ($data as $d) {
            $a[] = $e->__getBlank()->__fromArray($d);
        }

        return $a;

    }

}