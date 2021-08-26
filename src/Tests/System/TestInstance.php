<?php

namespace Tests;

class TestInstance {

    public string $DataType;
    public string $DataExpected;
    private \ReflectionClass $ReflectedClass;
    private \Tests\Falsifications $Falsifications;
    private \System\Databases $dbengine;
    
    public function __construct(
        private string $Class,
        private string $Function,
        private bool $crash = false
    ) {
        $this->Falsifications   = new \Tests\Falsifications(null, $crash);
        $this->dbengine         = new \System\Databases;
    }

    /**
     * Check if content provided is the expected
     *
     * @return bool
     */
    public function IsExpectedContentPresent() {
        $DatasExpected      = $this->RetrieveResponseIntent();
        $this->DataExpected = $DatasExpected;
        $DatasExpected      = explode("|", $DatasExpected);
        foreach ($DatasExpected as $DataExpected) {
            $DataExpected = ($DataExpected === "bool"? "boolean": $DataExpected);
            if (strcasecmp(trim($DataExpected, "\\"), $this->DataType) == 0 || $DataExpected === "void" && $this->DataType === "NULL") {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve data type or class name 
     *
     * @param mixed $dump
     * @return void
     */
    public function TracedBack(mixed $dump): void {
        if (is_object($dump)) {
            $this->DataType = get_class($dump) ?? "object";
            $this->DataType = str_replace("DynamicSchematics", "Schematics", $this->DataType);
            return;
        }
        $this->DataType = gettype($dump);
        return;
    }

    /**
     * Retrieve error
     *
     * @param string $ErrorCode
     * @return void
     */
    public function TracedBackError(string $ErrorCode): void {
        $this->DataType = "ErrorCode[{$ErrorCode}]";
        return;
    }
    
    /**
     * Get required arguements
     * 
     * @param string $class
     * @param string $function
     * 
     * @return mixed
     */
    public function GetRequiredArgs(string $_class, string $function): mixed {
        $class      = new \ReflectionClass($_class);
        $arguments  = (object) [ "binded" => [], "posted" => (object) [] ];
        $phpDocs    = $class->getMethod($function)->getDocComment();
        $phpDocs    = (!empty($phpDocs)? explode("*", $phpDocs): []);
        $tables     = json_decode(file_get_contents(__path__ . "/src/System/Schematics/indexes.json"), true);
        foreach ($phpDocs as $phpDoc) {
            $phpDoc = trim($phpDoc);
            if (preg_match('/@(.*)/', $phpDoc, $match)) {
                $matched  = $match[1] ?? null;
                $matched  = explode(" ", $matched)[0];
                if ($matched === "binded" || $matched === "posted") {
                    $match = $match[1];
                    $match = trim(str_replace($matched, "", $match));
                    preg_match('/(.*) "(.*)"/', $match, $pattern);
                    if (isset($pattern[1]) && isset($pattern[2]) && !empty($pattern[1]) && !empty($pattern[2])) {
                        [ $keyname, $value ] = [ $pattern[1], $pattern[2] ];
                        [ $kind, $func ] = explode("::", $value);
                        if ($kind === "datas") {
                            $_func = $func;
                            $match = [];
                            preg_match('/(.*)\((.*)\)/', $_func, $match);
                            if (!empty($match[0])) {
                                [ $func, $args ] = [ $match[1], explode(",", $match[2]) ?? [] ];
                                foreach ($args as &$arg) {
                                    $arg = trim($arg, "\"");
                                }
                                if (method_exists($this->Falsifications, "datas__{$func}")) {
                                    $value = call_user_func_array([$this->Falsifications, "datas__{$func}"], $args);
                                } else {
                                    echo "[\e[91mERROR\e[39m] Method \"{$func}\" not found for \"{$_func}\"\n";
                                    ($this->crash? exit(1): null);
                                }
                            } else {
                                echo "[\e[91mERROR\e[39m] Method pattern not found for \"{$_func}\"\n";
                                ($this->crash? exit(1): null);
                            }
                        } elseif ($kind === "reference") {
                            $func = explode("->", $func);
                            [ $classname, $parameter ] = [ trim($func[0], "\\"), $func[1] ?? null ];
                            if (in_array($classname, $tables)) {
                                $value = $this->GetDataFromTable($classname, $parameter);
                            } else {
                                $value = $this->Falsifications->GenerateSampleDatas($classname, $parameter);
                            }
                        }
                        if ($matched === "binded") {
                            $arguments->{$matched}[$keyname] = $value;
                        } else {
                            $arguments->{$matched}->{$keyname} = $value;
                        }
                    } else {
                        echo "[\e[91mERROR\e[39m] Argument pattern dosen't match on \"{$matched}\" at \"{$_class}@{$function}\"";
                        ($this->crash? exit(1): null);
                    }
                }
            }
        }
        return $arguments;
    }

    /**
     * Get response intent for doc comment
     * 
     * @return string|null
     */
    private function RetrieveResponseIntent(): ?string {
        $Reflector  = $this->GetReflectedClass($this->Class);
        $DocComment = $Reflector->getMethod($this->Function)->getDocComment() ?? null;
        preg_match('/@return (.*)\r\n/', $DocComment, $return);
        if (empty($return[1])) {
            preg_match('/@return (.*)\n/', $DocComment, $return);
            if (empty($return[1])) {
                preg_match('/@return (.*)\r/', $DocComment, $return);
                if (empty($return[1])) {
                    preg_match('/@return (.*)\n\r/', $DocComment, $return);
                }
            }
        }
        return $return[1] ?? "void";
    }

    /**
     * Get data from table
     * 
     * @param string $classname
     * @param string|null $parameter
     * 
     * @return string|null
     */
    private function GetDataFromTable(string $classname, string $parameter = null): ?string {
        $Annotations    = (new \System\Annotations($classname))->datas;
        $dbname         =    $Annotations["database"] ?? null;
        $table          = $Annotations["table"] ?? null;
        $rows           = $this->dbengine->Query($dbname, "SELECT * FROM `{$table}`");
        return (isset($rows[0]) && !empty($rows[0])? (!empty($parameter)? $rows[0]?->{$parameter}: $rows[0]): null);
    }

    /**
     * Create or return reflected class
     * 
     * @param string $Class
     * @return \ReflectionClass
     */
    private function GetReflectedClass(string $Class): \ReflectionClass {
        return (!empty($this->ReflectedClass)? $this->ReflectedClass: new \ReflectionClass($Class));
    }
}