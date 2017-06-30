<?php namespace Xdire\Forma\Interfaces;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/6/17
 */

use Xdire\Forma\SchemaEntities\ORMTypeStructure;

interface DBOArrangerInterface
{

    public static function getById(ORMTypeStructure $structure, array $valueArray);

    public static function getRawSingle($statement, ORMTypeStructure $structure);

    public static function getRawMultiple($statement, ORMTypeStructure $structure);

    public static function save(ORMTypeStructure $structure, array $valueArray);

    public static function update(ORMTypeStructure $structure, array $valueArray, array $indexArray = null);

    public static function delete(ORMTypeStructure $structure, array $valueArray);

    public function startTransaction();

    public function commitTransaction();

    public function rollbackTransaction();

    public static function escapeString($string);

    public static function escapeJSON($json);

    public function getDBInstance();

}