<?php
namespace Tests;

use Throwable;

class Databases {

    private \System\Databases $dbengine;

    public function __construct() {
        $this->dbengine = new \System\Databases;

        echo "Creation of the database structure and insertion of samples datas... (this operation may take a few seconds)\n - - - - - - - - - - - - - - - -\n";
        
        $DatabasesList  = $this->GetAllSchematics();
        $this->CreateAllTables($DatabasesList);

        echo " - - - - - - - - - - - - - - - -\n\n";
        $this->InsertDatasInTables($DatabasesList);
    }

    /**
     * Create all tables on all databases
     * 
     * @param array $DatabasesList
     * @return void
     */
    private function CreateAllTables(array $DatabasesList): void {
        foreach ($DatabasesList as $db) {
            $this->dbengine->Exec($db["dbname"], "DROP TABLE IF EXISTS `{$db["table"]}`;");
            echo "- Create table \e[34m\"{$db["table"]}\"\e[39m on database \e[34m\"{$db["dbname"]}\"\e[39m:\n";
            $SqlTable = "CREATE TABLE IF NOT EXISTS `{$db["table"]}` (";
            $i = 0;
            foreach ($db["columns"] as $column => $types) {
                echo "  - Create column \e[92m\"{$column}\"\e[39m with \e[94m\"{$types}\"\e[39m.\n";
                $types = explode("|", $types);
                $SqlTable .= "`{$column}`";
                foreach ($types as $type) {
                    preg_match('/\((.*)\)/', $type, $value);
                    if (isset($value[0]) && !empty($value[0])) {
                        $type = ((int) $value[1] === -1? str_replace($value[0], "", $type): $type);
                        $SqlTable .= " {$type} ";
                        break;
                    }
                }
                $SqlTable .= (in_array("nullable", $types)? "DEFAULT NULL": "NOT NULL");
                $SqlTable .= (in_array("primary", $types)? " PRIMARY KEY": null);
                $SqlTable .= (($i+1) == count($db["columns"])? ") ENGINE=MyISAM DEFAULT CHARSET=utf8": ", ");
                $i++;
            }
            $this->dbengine->Exec($db["dbname"], $SqlTable);
            echo "\n";
        }
        return;
    }

    /**
     * Insert samples datas in databases
     * 
     * @param array $DatabasesList
     * @return void
     */
    private function InsertDatasInTables(array $DatabasesList): void {
        foreach ($DatabasesList as $db) {
            $path = __path__ . "/src/Tests/SQL/{$db["dbname"]}/{$db["table"]}.sql";
            if (file_exists($path)) {
                $resp = $this->dbengine->Exec($db["dbname"], file_get_contents($path));
                if (is_object($resp) && get_class($resp) === "PDOException") {
                    echo "\e[31m  >>> [ERROR] Unable to insert datas in \e[39m\e[34m\"{$db["table"]}\"\e[31m on \e[39m\e[34m\"{$db["dbname"]}\"\e[39m, database engine return: \e[33m" . $resp->getMessage() . "\e[39m\n";
                } else {
                    echo ">>> [\e[32mOK\e[39m] Data(s) correctly inserted in \e[34m\"{$db["table"]}\"\e[39m on \e[34m\"{$db["dbname"]}\"\e[39m.\n";
                }
            }
        }
        return;
    }

    /**
     * Return all schematics
     * 
     * @return array
     */
    private function GetAllSchematics(): array {
        $db         = [];
        $indexes    = json_decode(file_get_contents(__path__ . "/src/System/Schematics/indexes.json"), true) ?? [];
        foreach ($indexes as $table => $index) {
            try {
                $ReflectedClass = new \ReflectionClass($index);
                $Annotations    = ((new \System\Annotations($ReflectedClass))->datas);
            } catch (Throwable $e) {
                $ReflectedClass = null;
                echo ">>> [\e[91mERROR\e[39m] \e[33m{$e->getMessage()}\e[39m on \e[34m\"{$index}\"\e[39m\n";
            }
            if (isset($Annotations["database"]) && !empty($Annotations["database"])) {
                $db[$table]["dbname"]   = $Annotations["database"];
                $db[$table]["table"]    = $Annotations["table"];
                $Properties             = (!empty($ReflectedClass)? $ReflectedClass->getProperties() ?? []: []);
                foreach ($Properties as $Property) {
                    $value = ($Property->getType()->isBuiltin()? $Property->getValue(new $index): new ($Property->getType()->getName()));
                    $value = (!is_object($value)? $value: "longtext(-1)");
                    $db[$table]["columns"][$Property->getName()] = $value . ($Property->getType()->allowsNull() === true? "|nullable": null);
                }
            }
        }
        return $db;
    }

}