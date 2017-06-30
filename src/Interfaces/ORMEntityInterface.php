<?php namespace Xdire\Forma\Interfaces;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/4/17
 */

interface ORMEntityInterface
{
    /**
     * @param array|null $data
     * @return self
     */
    public function __fromArray(array $data = null);

    /**
     * @param array|null $data
     * @return self
     */
    public function __getBlank(array $data = null);

    public static function find($id);

    public static function query();

    public function save();

    public function update();

    public function refresh();

    public function delete();

    public function fromArray();

    public function toArray();

    public function toJSON();

    public function get($originStorageFieldName);

}