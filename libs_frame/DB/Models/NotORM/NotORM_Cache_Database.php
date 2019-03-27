<?php
/**
 * Cache storing data to the "notorm" table in database
 */
namespace DB\Models\NotORM;

class NotORM_Cache_Database implements NotORM_Cache {
    private $connection;

    function __construct(\PDO $connection) {
        $this->connection = $connection;
    }

    function load($key) {
        $result = $this->connection->prepare("SELECT data FROM notorm WHERE id = ?");
        $result->execute(array($key));
        $return = $result->fetchColumn();
        if (!$return) {
            return null;
        }
        return unserialize($return);
    }

    function save($key, $data) {
        // REPLACE is not supported by PostgreSQL and MS SQL
        $parameters = array(serialize($data), $key);
        $result = $this->connection->prepare("UPDATE notorm SET data = ? WHERE id = ?");
        $result->execute($parameters);
        if (!$result->rowCount()) {
            $result = $this->connection->prepare("INSERT INTO notorm (data, id) VALUES (?, ?)");
            try {
                @$result->execute($parameters); // @ - ignore duplicate key error
            } catch (\PDOException $e) {
                if ($e->getCode() != "23000") { // "23000" - duplicate key
                    throw $e;
                }
            }
        }
    }
}