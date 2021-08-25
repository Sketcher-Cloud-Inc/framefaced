<?php

namespace Tests;

class Falsifications {

    private object $Faker;
    private array $indexes;
    private array $Objects;
    private object $datas;
    
    public function __construct(
        private int $nbIndexes,
        private bool $crash
    ) {
        $this->Faker    = \Faker\Factory::create();
        $this->indexes  = $this->ScanAllObjects();
        $this->datas    = (object) [
            "NewIdentifier" => function($args) {
                return (\Symfony\Component\Uid\Uuid::v4())->{"to{$args["type"]}"}();
            },
            "RandomValue" => function($args) {
                return $args[rand(0, (count($args) - 1))];
            },
            "RandomArray" => function($args) {
                return json_encode($args);
            },
            "RandomNumber" => function($args) {
                return (int) round(rand($args[0], $args[1]), (isset($args[2]) && !empty($args[2])? $args[2]: 1));
            },
            "RandomBool" => function($args) {
                return (bool) rand(0, 1);
            },
            "FakeFirstname" => function() {
                return $this->Faker->firstName();
            },
            "FakeLastname" => function() {
                return $this->Faker->lastName();
            },
            "FakeEmail" => function() {
                return $this->Faker->email();
            },
            "FakeStreetAddress" => function() {
                return $this->Faker->streetAddress();
            },
            "FakePostCode" => function() {
                return $this->Faker->postcode();
            },
            "FakeCity" => function() {
                return $this->Faker->city();
            },
            "FakeState" => function() {
                return $this->Faker->state();
            },
            "empty" => function() {
                return null;
            },
            "CurrentDateTime" => function($args) {
                return (new \DateTime())->format(__DATE_FORMAT__);
            }
        ];
        $this->Objects  = $this->GetObjectsAnnotations();
        $FakeDatas      = $this->GenerateFakeDatas();
        $this->CreateFakeSqlFiles($FakeDatas);
    }

    /**
     * Generate SQL and save in a file
     * 
     * @param array $Objects
     * @return void
     */
    private function CreateFakeSqlFiles(array $Objects): void {
        echo "\n - - - - - - - - - - Start generate SQL files - - - - - - - - - -\n\n";
        foreach ($Objects as $Object) {
            if (!empty($Object["dbname"]) && !empty($Object["table"])) {
                (realpath(__path__ . "/src/Tests/SQL/{$Object["dbname"]}") === false? mkdir(__path__ . "/src/Tests/SQL/{$Object["dbname"]}", 0777, true): null);
                $path   = __path__ . "/src/Tests/SQL/{$Object["dbname"]}/{$Object["table"]}.sql";
                $SQL    = "INSERT INTO `{$Object["table"]}` (";
                $keys   = array_keys($Object["generated"][0]);
                foreach ($keys as $i => $keyname) {
                    $SQL .= "`{$keyname}`" . (count($keys) !== ($i + 1)? ", ": ") VALUES");
                }

                foreach ($Object["generated"] as $i => $ObjectDatas) {
                    $SQL    .= ($i > 0? ", (": " (");
                    $n      = 0;
                    $keys   = array_keys($ObjectDatas);
                    foreach ($ObjectDatas as $ObjectData) {
                        $ObjectData = (is_bool($ObjectData)? (int) $ObjectData: $ObjectData);
                        $ObjectData = (!empty($ObjectData) || $ObjectData === 0? base64_encode($ObjectData): null);
                        $SQL .= (!empty($ObjectData) || $ObjectData === 0? "\"{$ObjectData}\"": "NULL") . (count($keys) !== ($n + 1)? ", ": ")");
                        $n++;
                    }
                }
                $SQL .= ";";
                file_put_contents($path, $SQL);
                $path = realpath($path);
                echo "[\e[32mOK\e[39m] Data as been generated and saved for \e[92m\"{$Object["dbname"]}@{$Object["table"]}\"\e[39m in \e[94m\"{$path}\"\e[39m.\n";
            }
        }
        return;
    }

    /**
     * Generate fake datas
     * 
     * @return array
     */
    private function GenerateFakeDatas(): array {
        echo "\n - - - - - - - - - - Start generate fake datas - - - - - - - - - -\n\n";
        $SqlObjects         = [];
        $ReferencesCatalog  = [];
        foreach ($this->Objects as $ObjName => $Object) {
            $usable = true;
            foreach ($Object["properties"] as $PropertyName => $PropertyTypes) {
                if (isset($PropertyTypes["source"]) && !empty($PropertyTypes["source"])) {
                    [ $type, $func ] = explode("::", $PropertyTypes["source"]);
                    if ($type === "datas") {
                        preg_match('/(.*)\((.*)\)/', $func, $args);
                        $func   = $args[1] ?? null;
                        $_args  = [];
                        if (!empty($args)) {
                            $args = explode(",", $args[2]);
                            foreach ($args as $arg) {
                                $arg = trim($arg);
                                $arg = explode(":", $arg);
                                $_args = array_merge($_args, (!empty($arg[1])? [ "$arg[0]" => trim($arg[1]) ]: [ $arg[0] ]));
                            }
                            try {
                                $func = $this->datas?->{$func} ?? null;
                                if (!empty($func)) {
                                    for ($i = 0; $i <= $this->nbIndexes; $i++) {
                                        $Object["generated"][$i][$PropertyName] = $func($_args) ?? null;
                                    }
                                } else {
                                    echo "[\e[91mERROR\e[39m] Source function not found in \"{$PropertyName}\" on \"{$ObjName}\"!";
                                    ($this->crash? exit(1): null);
                                }
                            } catch (\Throwable $e) {
                                echo "[\e[91mERROR\e[39m] Source function return error \"{$e->getMessage()}\" in \"{$PropertyName}\" on \"{$ObjName}\"!";
                                ($this->crash? exit(1): null);
                            }
                        } else {
                            echo "[\e[91mERROR\e[39m] Source function is not corretcly declared in \"{$PropertyName}\" on \"{$ObjName}\"!";
                            ($this->crash? exit(1): null);
                        }
                    } elseif ($type === "reference") {
                        for ($i = 0; $i <= $this->nbIndexes; $i++) {
                            $ReferencesCatalog[$ObjName][$i][$PropertyName] = [$Object["properties"][$PropertyName]["values"], $func];
                            $Object["generated"][$i][$PropertyName] = &$ReferencesCatalog[$ObjName][$i][$PropertyName];
                        }
                    } else {
                        echo "[\e[91mERROR\e[39m] Source kind provider not found in \"{$PropertyName}\" on \"{$ObjName}\"!";
                        ($this->crash? exit(1): null);
                    }
                    $Object["class"] = $ObjName;
                } else {
                    echo "[\e[33mWARNING\e[39m] Can't use \e[92m\"{$ObjName}\"\e[39m: Source must be defined in \e[94m\"{$PropertyName}\"\e[39m.\n";
                    $usable = false;
                    ($this->crash? exit(1): null);
                }
            }
            if ($usable) {
                echo "[\e[32mOK\e[39m] Datas as been generated for \e[92m\"{$Object["dbname"]}@{$Object["table"]}\"\e[39m from \e[94m\"{$ObjName}\"\e[39m.\n"; 
            }
            $SqlObjects = array_merge($SqlObjects, ($usable? [$Object]: []));
        }
        $this->ParseReferencesCatalog($SqlObjects, $ReferencesCatalog);
        return $SqlObjects;
    }

    /**
     * Get indexed objects annotations
     * 
     * @return array
     */
    private function GetObjectsAnnotations(): array {
        $annotations = [];
        foreach ($this->indexes as $index) {
            $ReflectedClass = new \ReflectionClass($index);
            $_Annotations   = (new \System\Annotations($ReflectedClass))?->datas ?? [];
            $annotations[$index]["dbname"]  = $_Annotations["database"] ?? null;
            $annotations[$index]["table"]   = $_Annotations["table"] ?? null;
            foreach ($ReflectedClass->getProperties() as $Property) {
                $phpDoc = $Property->getDocComment();
                $annotations[$index]["properties"][$Property->getName()]["nullable"] = $Property->getType()->allowsNull();
                if (preg_match_all('/@(.*) "(.*)"\r\n/', $phpDoc, $match) || preg_match_all('/@(.*) "(.*)"\n/', $phpDoc, $match) || preg_match_all('/@(.*) "(.*)"\r/', $phpDoc, $match) || preg_match_all('/@(.*) "(.*)"\n\r/', $phpDoc, $match)) {
                    $keys   = $match[1] ?? [];
                    $values = $match[2] ?? [];
                    $annotations[$index]["properties"][$Property->getName()]["values"] = $Property->getDefaultValue();
                    foreach ($keys as $i => $key) {
                        $annotations[$index]["properties"][$Property->getName()][$key] = ($values[$i] ?? null);
                    }
                }
            }
        }
        return $annotations;
    }

    /**
     * Sets up the reference parameters
     * 
     * @param array $SqlObjects
     * @param array $ReferencesCatalog
     * 
     * @return void
     */
    private function ParseReferencesCatalog(array $SqlObjects, array &$ReferencesCatalog): void {
        foreach ($ReferencesCatalog as &$References) {
            foreach ($References as $i => &$Reference) {
                foreach ($Reference as $n => $Ref) {
                    [ $values, $Ref ] = $Ref;
                    $class      = explode("->", $Ref);
                    $keyname    = $class[1] ?? null;
                    $class      = $class[0] ?? null;
                    if (!empty($class)) {
                        $set = false;
                        foreach ($SqlObjects as $SqlObject) {
                            if (trim($SqlObject["class"], '\\') === trim($class, '\\')) {
                                $set = true;
                                $Ref = (!empty($keyname)? ($SqlObject["generated"][$i][$keyname] ?? null): json_encode($SqlObject["generated"][$i]));
                                $Ref = (in_array("multi-array", explode("|", $values))? (!empty($keyname)? "[\"{$Ref}\"]": "[{$Ref}]"): $Ref);
                                $Reference[$n] = $Ref;
                                break;
                            }
                        }
                        if (!$set) {
                            echo "[\e[91mERROR\e[39m] Unable to find class \e[92m\"{$class}\"\e[39m on generated datas.\n";
                            ($this->crash? exit(1): null);
                        }
                    }
                }
            }
        }
        return;
    }
    
    /**
     * Scan and find all objects
     * 
     * @return void
     */
    private function ScanAllObjects(?string $path = null): array {
        $path       = realpath((!empty($path)? $path: __path__ . "/src/System/Schematics/{$path}"));
        $indexes    = [];
        foreach (scandir($path) as $scanned) {
            if ($scanned !== "." && $scanned !== "..") {
                $scanned = "{$path}/{$scanned}";
                if (!is_dir($scanned)) {
                    if (pathinfo($scanned, PATHINFO_EXTENSION) === "php") {
                        $content = file_get_contents($scanned);
                        preg_match('/namespace (.*);/', $content, $namespace);
                        preg_match('/class (.*){/', $content, $class);
                        $namespace = $namespace[1] ?? null;
                        $class = $class[1] ?? null;
                        if (!empty($namespace) && !empty($class)) {
                            $class = trim($class);
                            $class = "{$namespace}\\{$class}";
                            array_push($indexes, $class);
                        }
                    }
                } else {
                    $indexes = array_merge($indexes, $this->ScanAllObjects($scanned));
                }
            }
        }
        return $indexes;
    }

}