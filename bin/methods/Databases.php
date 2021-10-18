<?php

namespace Console;

class Databases {

    private $crash;

    /**
     * Manage your databases
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {   
            $this->LocalTestSplAutoloader();
            $this->crash = (in_array("--CrashOnFailure", $this->Commitments->arguments)? true: false);
            if (in_array("--import", $this->Commitments->arguments)) {
                new \Tests\Databases($this->crash);
            } elseif (in_array("--indexes", $this->Commitments->arguments)) {
                $indexes = $this->ScanObjectsSchematicsAndIndexes();
                file_put_contents(__path__ . "/src/System/Schematics/indexes.json", json_encode($indexes));
            } elseif (in_array("--falsifications", $Commitments->arguments)) {
                (new \Tests\Falsifications(
                    (in_array("--entries", $Commitments->arguments)? (int) $Commitments->ArgsValues["--entries"] ?? 10: 10),
                    $this->crash
                ))->CreateJsonSamples();
            }
        }
        return;
    }

    /**
     * Autoload "Tests" class
     * 
     * @return void
     */
    private function LocalTestSplAutoloader(): void {
        spl_autoload_register(function ($class) {
            $namespace = explode("\\", $class);
            $class      = $namespace[1] ?? null;
            $namespace  = $namespace[0] ?? null;
            if ($namespace === "Tests") {
                include __path__ . "/src/Tests/System/{$class}.php";
            }
        });
    }

    /**
     * Scan and find all object required indexes for database engine
     * 
     * @return void
     */
    private function ScanObjectsSchematicsAndIndexes(?string $path = null): array {
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
                            $Annotation = (new \System\Annotations($class))?->datas ?? [];
                            if (isset($Annotation["database"]) && !empty($Annotation["database"]) && isset($Annotation["collection"]) && !empty($Annotation["collection"])) {
                                echo "- New collection found \"{$Annotation["collection"]}\" on \"{$class}\"\n";
                                $indexes = array_merge($indexes, [ "{$Annotation["database"]}.{$Annotation["collection"]}" => $class ]);
                            }
                        }
                    }
                } else {
                    $indexes = array_merge($indexes, $this->ScanObjectsSchematicsAndIndexes($scanned));
                }
            }
        }
        return $indexes;
    }

    /**
     * Documentation (used by Help class)
     *
     * @param  Help $Helper
     * @return void
     */
    private function ShowHelper(?\Console\Help &$Helper): bool {
        if (!empty($Helper)) {
            $this->Helper->AddHelper("Manage your databases", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [
                "Use --indexes for scan and index all collections on objects",
                "Use --falsifications for create fake datas (use --entries to specify how many rows)",
                "Use --import to import new collections & datas in your databases."
            ]);
            return true;
        }
        return false;
    }

}