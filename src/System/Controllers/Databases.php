<?php

namespace System;

class Databases {

    private \System\ObjectsResolver $ObjectsResolver;
    private array $indexes;
    private object $databases;

    public function __construct(){
        $this->ObjectsResolver  = new \System\ObjectsResolver;
        $this->indexes          = json_decode(file_get_contents(__path__ . "/src/System/Schematics/indexes.json"), true) ?? [];
        $this->databases        = $this->ParseAllDatabases();
    }
    
    /**
     * Send query to database
     *
     * @param  string $dbname
     * @param  string $request
     * @param  array $parameters
     * @return void
     */
    public function Query(string $dbname, string $request, array $parameters = [], array $selectors = []): ?array {
        $db = &$this->databases->{$dbname} ?? null;
        if (!empty($db)) {
            $this->dbinit($dbname);
            if (!empty($db->obj)) {
                $this->AddSelectorsOnRequest($request, $parameters, $selectors);
                $resp = $db->obj->prepare($request);
                $resp->execute($parameters);
                $datas = $resp->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                throw new \Exception("[DBEngine] Database \"{$dbname}\" not found!");
            }
            return $this->ParseTableSchematics($resp->getColumnMeta(0)["table"], $datas) ?? null;
        }
    }

    /**
     * Send request to database
     *
     * @param  string $dbname
     * @param  string $request
     * @param  array $parameters
     * @return void
     */
    public function Exec(string $dbname, string $request, array $parameters = []): ?\PDOStatement {
        $db = &$this->databases->{$dbname} ?? null;
        if (!empty($db)) {
            $this->dbinit($dbname);
            if (!empty($db->obj)) {
                $resp = $db->obj->prepare($request);
                try {
                    $resp->execute($parameters);
                } catch (\PDOException $e) {
                    if (__debug_mode__) {
                        throw $e;
                    }
                }
            } else {
                throw new \Exception("[DBEngine - System] Database \"{$dbname}\" not found!");
            }
            return (!isset($e)? $resp: $e);
        }
    }

    /**
     * Generate valid selectors from request parameter parsing
     * 
     * @param array $selectors
     * 
     * @return array
     */
    public function GenerateSelectors(array $selectors): array {
        foreach ($selectors as $i => $selector) {
            if ($selector === null) {
                unset($selectors[$i]);
            }
        }
        return $selectors;
    }

    /**
     * Add selector (where) on SQL request
     * 
     * @param string $request
     * @param array $parameters
     * @param array $selectors
     * 
     * @return void
     */
    private function AddSelectorsOnRequest(string &$request, array &$parameters, array $selectors): void {
        $matched = preg_match('/%selectors%/', $request);
        $request = trim($request, ";");
        if (!empty($selectors) || $matched) {
            $cond   = null;
            $i      = 1;
            $keys   = array_keys($selectors);
            foreach ($selectors as $keyname => $value) {
                $name   = "SELECTOR_ENGINE__" . strtoupper($keyname);
                $cond   .= "`{$keyname}` " . ($value !== NULL? "= :{$name}": "IS NULL") . (isset($keys[$i])? " AND ": null);
                if ($value !== NULL) {
                    $parameters[$name] = $value;
                }
                $i++;
            }
            $request = ($matched? str_replace("%selectors%", $cond, $request): "{$request} WHERE {$cond};");
        }
        return;
    }
    
    /**
     * Initialize a database
     *
     * @param  string $dbname
     * @return void
     */
    private function dbinit(string $dbname): void {
        $db = &$this->databases->{$dbname};
        if (!empty($db) && empty($db->obj)) {
            try {
                $this->databases->{$dbname}->obj = new \PDO("mysql:host={$db->hostname};port={$db->port};dbname={$db->database};charset={$db->charset}", $db->access->username, $db->access->password);
            } catch (\PDOException $e) {
                $this->databases->{$dbname}->obj = null;
            }
        }
        return;
    }
    
    /**
     * Parse all databases
     *
     * @return object
     */
    private function ParseAllDatabases(): object {
        $databases = file_get_contents(__path__ . "/src/conf/databases.json") ?? null;
        $databases = json_decode($databases, false) ?? [];
        foreach ($databases as $db => &$database) {
            $db                 = strtoupper($db);
            $database->obj      = null;
            $database->access   = (object) [];
            foreach ($_ENV as $keyname => $value) {
                $value = (!empty($value)? $value: null);
                if ($keyname === "DATABASE_{$db}_USER") {
                    $database->access->username = $value;
                } elseif ($keyname === "DATABASE_{$db}_PASSWORD") {
                    $database->access->password = $value;
                }
            }
        }
        return $databases;
    }
    
    /**
     * Parse table schematics
     *
     * @param  string $table
     * @param  array $datas
     * @return array
     */
    private function ParseTableSchematics(string $table, array $datas): ?array {
        $table = $this->indexes[$table] ?? null;
        if (!empty($table)) {
            foreach ($datas as $i => &$data) {
                $data = $this->ObjectsResolver->NewResolve($table, $data);
                if ($data === null) {
                    unset($datas[$i]);
                }
            }
        }
        return array_values($datas);
    }
}