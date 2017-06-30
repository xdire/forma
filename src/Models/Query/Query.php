<?php namespace Xdire\Forma\Models\Query;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 2/16/17
 */

use Xdire\Forma\Interfaces\ORMEntityInterface;
use Xdire\Forma\Models\Query\Exec\RawQueryExecutor;
use Xdire\Forma\SchemaEntities\ORMTypeStructure;

class Query
{
    /** @var ORMEntityInterface|null  */
    private $entity = null;
    /** @var ORMTypeStructure|null  */
    private $structure = null;

    function __construct(ORMEntityInterface $e, ORMTypeStructure $s)
    {
        $this->entity = $e;
        $this->structure = $s;
    }

    public function __getAttachedEntity() {
        return $this->entity;
    }

    public function __getAttachedStructure() {
        return $this->structure;
    }

    public function select() {

    }

    public function where() {

    }

    public function join() {

    }

    public function raw($query) {
        return new RawQueryExecutor($query, $this);
    }

}