<?php
/**
Copyright (c) 2014, Revoplay.de Development
http://www.revoplay.de
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

if (!defined('RunALC')) {
    exit;
}

class db {
    protected static $dbConf = array("default" => array("driver" => "mysql","db" => "cdcol", "db_host" => "localhost", "db_user" => "root", "db_pw" => ""));
    protected static $instances = array();

    protected $active = false;
    protected $dbHandle = null;
    protected $lastInsertId = false;
    protected $rowCount = false;
    protected $queryCounter = 0;
    protected $active_driver = '';
    protected $connection_pooling = true;
    protected $connection_encrypting = true;
    protected $mysql_buffered_query = true;
    protected $prefix = '';

    private function __clone() { }

    /**
     * Erstellt das PDO Objekt mit vorhandener Konfiguration
     * @category PDO Database
     * @param string $active = "default"
     * @throws PDOException
     */
    protected function connect($active = "default") {
        if (!isset(self::$dbConf[$active]))
            throw new PDOException("No supported connection scheme");

        $dbConf = self::$dbConf[$active];
        try {
            if(!$dsn = $this->dsn($active))
                throw new Exception("PDO driver is missing");

            $db = new PDO($dsn, $dbConf['db_user'], $dbConf['db_pw']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->query("set character set utf8");
            $db->query("set names utf8");

            $this->dbHandle = $db;
            $this->active = $active; //mark as active
            $this->prefix = $dbConf['prefix'];
        } catch (PDOException $ex) {
            throw new PDOException("Connection Exception: " . $ex->getMessage());
        }
    }

    public static function setConfig($active = "default", array $data) {
        if(isset($data['db']) && isset($data['db_host']) && isset($data['db_host']) && isset($data['db_user']) && isset($data['db_pw'])) {
            self::$dbConf[$active] = $data;
            return true;
        }

        return false;
    }

    public static function getInstance($active = "default") {
        if (!isset(self::$dbConf[$active])) {
            throw new Exception("Unexisting db-config $active");
        }

        if (!isset(self::$instances[$active]) || !isInstanceOf('db')) {
            self::$instances[$active] = new db($active);
            self::$instances[$active]->connect($active);
        }

        return self::$instances[$active];
    }

    public static function getPrefix($active = "default") {
        if (!isset(self::$dbConf[$active])) {
            throw new Exception("Unexisting db-config $active");
        }

        return self::$instances[$active]->prefix();
    }

    public function prefix() {
        return $this->prefix;
    }

    public function disconnect($active = "") {
        if(empty($active)) {
            unset(self::$instances[$this->active]);
        } else {
            unset(self::$instances[$active]);
        }

        $this->dbHandle = null;
    }

    public function getHandle() {
        return $this->dbHandle;
    }

    public function lastInsertId() {
        return $this->lastInsertId;
    }

    public function rowCount() {
        return $this->rowCount;
    }

    protected function run_query($qry, array $params, $type) {
        if (in_array($type, array("insert", "select", "update", "delete")) === false) {
            throw new Exception("Unsupported Query Type");
        }

        $this->lastInsertId = false;
        $this->rowCount = false;
        $stmnt = $this->active_driver == 'mysql' ? $this->dbHandle->prepare($qry, array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => $this->mysql_buffered_query)) : $this->dbHandle->prepare($qry);

        try {
            $success = (count($params) !== 0) ? $stmnt->execute($params) : $stmnt->execute();
            $this->queryCounter++;

            if (!$success) return false;
            if ($type === "insert")
                $this->lastInsertId = $this->dbHandle->lastInsertId();

            $this->rowCount = $stmnt->rowCount();

            return ($type === "select") ? $stmnt : true;
        } catch (PDOException $ex) {
            Debugger::sql_error_handler($qry,$params,$ex->getMessage());
            throw new PDOException("PDO-Exception: " . $ex->getMessage());
        }
    }

    protected function check_driver($use_driver) {
        foreach(PDO::getAvailableDrivers() as $driver) {
            if($use_driver == $driver) return true;
        }

        return false;
    }

    protected function dsn($active) {
        $dbConf = self::$dbConf[$active];
        if(!$this->check_driver($dbConf['driver']))
            return false;

        $this->active_driver = $dbConf['driver'];
        $dsn= sprintf('%s:', $dbConf['driver']);
        switch($dbConf['driver']) {
            case 'mysql':
            case 'pgsql':
                $dsn .= sprintf('host=%s;dbname=%s', $dbConf['db_host'], $dbConf['db']);
                break;
            case 'sqlsrv':
                $dsn .= sprintf('Server=%s;1433;Database=%s', $dbConf['db_host'], $dbConf['db']);
                if($this->connection_pooling) $dsn .= ';ConnectionPooling=1';
                if($this->connection_encrypting) $dsn .= ';Encrypt=1';
            break;
        }

        return $dsn;
    }

    protected function getQueryType($qry) {
        list($type, ) = explode(" ", strtolower($qry), 2);
        return $type;
    }

    public function delete($qry, array $params = array()) {
        if (($type = $this->getQueryType($qry)) !== "delete") {
            throw new Exception("Incorrect Delete Query");
        }

        return $this->run_query($qry, $params, $type);
    }

    public function update($qry, array $params = array()) {
        if (($type = $this->getQueryType($qry)) !== "update") {
            throw new Exception("Incorrect Update Query");
        }

        return $this->run_query($qry, $params, $type);
    }

    public function insert($qry, array $params = array()) {
        if (($type = $this->getQueryType($qry)) !== "insert") {
            throw new Exception("Incorrect Insert Query");
        }

        return $this->run_query($qry, $params, $type);
    }

    public function select_foreach($qry, array $params = array()) {
        if (($type = $this->getQueryType($qry)) !== "select")
            throw new Exception("Incorrect Select Query");

        if ($stmnt = $this->run_query($qry, $params, $type)) {
            return convert::objectToArray($stmnt->fetchAll(PDO::FETCH_ASSOC));
        }

        return false;
    }

    public function select($qry, array $params = array()) {
        $sql = $this->select_foreach($qry,$params);
        return !$sql ? false : $sql[0];
    }

    public function db_selectSingle($qry, array $params = array(), $field = false) {
        if (($type = $this->getQueryType($qry)) !== "select")
            throw new Exception("Incorrect Select Query");

        if ($stmnt = $this->run_query($qry, $params, $type)) {
            $res = $stmnt->fetch(PDO::FETCH_ASSOC);
            return ($field === false) ? $res : $res[$field];
        }
        else
            return false;
    }

    public function query($qry) {
        $this->lastInsertId = false;
        $this->rowCount = false;
        $this->rowCount = $this->dbHandle->exec($qry);
        $this->queryCounter++;
    }

    public function getQueryCounter() {
        return $this->queryCounter;
    }

    public function quote($str) {
        return $this->dbHandle->quote($str);
    }
}