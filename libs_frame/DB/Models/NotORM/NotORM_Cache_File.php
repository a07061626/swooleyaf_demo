<?php
/**
 * Created by PhpStorm.
 * User: jw
 * Date: 17-4-3
 * Time: 下午3:06
 */
namespace DB\Models\NotORM;

/**
 * Cache using file
 */
class NotORM_Cache_File implements NotORM_Cache {
    private $filename, $data = array();

    function __construct($filename) {
        $this->filename = $filename;
        $this->data = unserialize(@file_get_contents($filename)); // @ - file may not exist
    }

    function load($key) {
        if (!isset($this->data[$key])) {
            return null;
        }
        return $this->data[$key];
    }

    function save($key, $data) {
        if (!isset($this->data[$key]) || $this->data[$key] !== $data) {
            $this->data[$key] = $data;
            file_put_contents($this->filename, serialize($this->data), LOCK_EX);
        }
    }
}