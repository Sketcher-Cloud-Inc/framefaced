<?php

namespace Tests;

class Falsifications {

    private \Faker\Generator $Faker;
    private array $indexes;

    public function __construct(
        private ?int $iterations = 32,
        private bool $crash = false
    ){
        $this->iterations   = $this->iterations ?? 32;
        $this->Faker        = \Faker\Factory::create();
        $this->indexes      = $this->ScanAllObjects();
    }

    /**
     * Create SQL files
     * 
     * @return void
     */
    public function CreateSqlSamples(): void {
        echo "Starting generation samples datas...\n";
        $datas = $this->GenerateSampleDatas();
        echo "Exporting generated datasets on SQL files...\n";
        $indexes = json_decode(file_get_contents(__path__ . "/src/System/Schematics/indexes.json"), false);
        foreach ($indexes as $table => $class) {
            if (isset($datas[$class]) && !empty($datas[$class]) && !empty($datas[$class][0])) {
                $dbname = (new \System\Annotations($class))->datas["database"];
                (realpath(__path__ . "/src/Tests/SQL/{$dbname}") === false? mkdir(__path__ . "/src/Tests/SQL/{$dbname}", 0777, true): null);
                $path   = __path__ . "/src/Tests/SQL/{$dbname}/{$table}.sql";
                $SQL    = "INSERT INTO `{$table}` (";
                $keys   = array_keys($datas[$class][0]);
                foreach ($keys as $i => $keyname) {
                    $SQL .= "`{$keyname}`" . (count($keys) !== ($i + 1)? ", ": ") VALUES");
                }
                foreach ($datas[$class] as $i => $data) {
                    $SQL .= ($i > 0? ", (": " (");
                    $n = 0;
                    $keys = array_keys($data);
                    foreach ($data as $row) {
                        $row = (is_bool($row)? (int) $row: $row);
                        $row = (!empty($row) || $row === 0? base64_encode((is_array($row) || is_object($row)? json_encode($row): $row)): null);
                        $SQL .= (!empty($row) || $row === 0? "\"{$row}\"": "NULL") . (count($keys) !== ($n + 1)? ", ": ")");
                        $n++;
                    }
                }
                $SQL .= ";";
                file_put_contents($path, $SQL);
                $path = realpath($path);
                echo "[\e[32mOK\e[39m] Data as been generated and saved for \e[92m\"{$class} >>> {$dbname}@{$table}\"\e[39m in \e[94m\"{$path}\"\e[39m.\n";
            } else {
                echo "[\e[91mERROR\e[39m] Missing generated datas for table \"{$table}\" on \"{$class}\"\n";
                ($this->crash? exit(1): null);
            }
        }
        return;
    }

    /**
     * Generate fake datas from objects schematics
     * 
     * @param string|null $class
     * @param string|null $PropertyName
     * @param int|null $ite
     * 
     * @return void
     */
    public function GenerateSampleDatas(string $class = null, string $PropertyName = null, int $ite = null): mixed {
        $this->iterations       = (!empty($class) && !empty($PropertyName)? 1: $ite) ?? $this->iterations;
        $datas                  = [];
        $ReferencesCatalog      = [];
        $ReferencesCatalogKind  = [];
        $class = trim($class, "\\");
        foreach ($this->indexes as $index) {
            if (empty($class) || $index === $class) {
                if (class_exists($index)) {
                    $ReflectedClass = new \ReflectionClass($index);
                    $datas[$index]  = (!empty($datas[$index])? $datas[$index]: []);
                    foreach ($ReflectedClass->getProperties() as $property) {
                        if (empty($PropertyName) || $property->getName() === $PropertyName) {
                            $Annotations            = (new \System\Annotations($ReflectedClass, null, $property->getName()))->datas;
                            $Annotations["kind"]    = (isset($Annotations["kind"]) && !empty($Annotations["kind"])? ($Annotations["kind"] === "array"? $Annotations["kind"]: null): null);
                            if (isset($Annotations["source"]) && !empty($Annotations["source"])) {
                                [ $kind, $brush ] = explode("::", $Annotations["source"]);
                                if ($kind === "datas") {
                                    $match = [];
                                    preg_match('/(.*)\((.*)\)/', $brush, $match);
                                    if (!empty($match[0])) {
                                        [ $func, $args ] = [ $match[1], explode(",", $match[2]) ?? [] ];
                                        foreach ($args as &$arg) {
                                            $arg = trim($arg, "\"");
                                        }
                                        if (method_exists(__CLASS__, "datas__{$func}")) {
                                            for ($iterations = 0; $iterations <= ($this->iterations - 1); $iterations++) {
                                                $value = call_user_func_array([__CLASS__, "datas__{$func}"], $args);
                                                $datas[$index][$iterations][$property->getName()] = ($Annotations["kind"] === "array"? [ $value ]: $value);
                                            }
                                        } else {
                                            echo "[\e[91mERROR\e[39m] Method \"{$func}\" not found for \"{$index}->{$property->getName()}\"\n";
                                            ($this->crash? exit(1): null);
                                        }
                                    } else {
                                        echo "[\e[91mERROR\e[39m] Source pattern of function not found for \"{$index}->{$property->getName()}\"\n";
                                        ($this->crash? exit(1): null);
                                    }
                                } elseif ($kind === "reference") {
                                    for ($iterations = 0; $iterations <= ($this->iterations - 1); $iterations++) {
                                        $ReferencesCatalog[$index][$iterations][$property->getName()]       = $brush;
                                        $ReferencesCatalogKind[$index][$iterations][$property->getName()]   = $Annotations["kind"];
                                        $datas[$index][$iterations][$property->getName()]                   = &$ReferencesCatalog[$index][$iterations][$property->getName()];
                                    }
                                } else {
                                    echo "[\e[91mERROR\e[39m] Undefined source kind for \"{$index}->{$property->getName()}\"\n";
                                    ($this->crash? exit(1): null);
                                }
                            } else {
                                echo "[\e[91mERROR\e[39m] Unable to find source for \"{$index}->{$property->getName()}\"\n";
                                ($this->crash? exit(1): null);
                            }
                        }
                    }
                    
                } else {
                    echo "[\e[91mERROR\e[39m] Unable to load class \"{$index}\"\n";
                    ($this->crash? exit(1): null);
                }
            }
        }
        if (!empty($datas)) {
            foreach ($ReferencesCatalog as $classname => &$ReferenceCatalog) {
                foreach ($ReferenceCatalog as $i => &$Object) {
                    foreach ($Object as $ref => &$reference) {
                        $reference  = explode("->", $reference);
                        $kind       = $ReferencesCatalogKind[$classname][$i][$ref] ?? null;
                        [ $reference, $property ] = [ trim($reference[0], "\\"), $reference[1] ?? null ];
                        $reference  = (!empty($property)? $datas[$reference][$i][$property] ?? null: $datas[$reference][$i]) ?? null;
                        $reference  = ($kind === "array"? [ $reference ]: $reference);
                    }
                }
            }
        } else {
            echo "[\e[91mERROR\e[39m] Unable to find class or property for \"{$class}->{$PropertyName}\"\n";
            ($this->crash? exit(1): null);
        }
        return (!empty($class)? (!empty($PropertyName)? $datas[$class][0][$PropertyName]: $datas[$class][0]): $datas);
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

    /**
     * Generate new identifier
     * 
     * @param string|null $type
     * 
     * @return string
     */
    public function datas__NewIdentifier(string $type = null): string {
        $type = $type ?? "Rfc4122";
        return (\Symfony\Component\Uid\Uuid::v4())->{"to{$type}"}();
    }

    /**
     * Return random value
     * 
     * @param array $args
     * 
     * @return string
     */
    public function datas__RandomValue(string ...$args): string {
        return $args[rand(0, (count($args) - 1))]; 
    }

    /**
     * Convert arguments to array
     * 
     * @param array $array
     * 
     * @return string
     */
    public function datas__RandomArray(string ...$array): string {
        return json_encode($array);
    }

    /**
     * Return random number between two values
     * 
     * @param string $min
     * @param string $max
     * 
     * @return string
     */
    public function datas__RandomNumber(string $min, string $max): string {
        [ $min, $max ] = [ (int) $min, (int) $max ];
        return (int) round(rand($min, $max), $max); 
    }

    /**
     * Return random bool
     * 
     * @return string
     */
    public function datas__RandomBool(): string {
        return (bool) rand(0, 1); 
    }

    /**
     * Return fake firstname
     * 
     * @return string
     */
    public function datas__FakeFirstname(): string {
        return $this->Faker->firstName(); 
    }

    /**
     * Return fake lastname
     * 
     * @return string
     */
    public function datas__FakeLastname(): string {
        return $this->Faker->lastName(); 
    }

    /**
     * Return fake email
     * 
     * @return string
     */
    public function datas__FakeEmail(): string {
        return $this->Faker->email(); 
    }

    /**
     * Return fake street address (USA)
     * 
     * @return string
     */
    public function datas__FakeStreetAddress(): string {
        return $this->Faker->streetAddress(); 
    }

    /**
     * Return fake postcode (USA)
     * 
     * @return string
     */
    public function datas__FakePostCode(): string {
        return $this->Faker->postcode(); 
    }

    /**
     * Return fake city (USA)
     * 
     * @return string
     */
    public function datas__FakeCity(): string {
        return $this->Faker->city(); 
    }

    /**
     * Return fake state (USA)
     * 
     * @return string
     */
    public function datas__FakeState(): string {
        return $this->Faker->state(); 
    }

    /**
     * Return null
     * 
     * @return string|null
     */
    public function datas__empty(): ?string {
        return null; 
    }

    /**
     * Return current datetime
     * 
     * @return string
     */
    public function datas__CurrentDateTime(): string {
        return (new \DateTime())->format(__DATE_FORMAT__); 
    }
}