<?php

namespace Console;

use Closure;
use Exception;

class Tests {

    /**
     * Start test of the features calling in the router
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {   
            $this->LocalTestSplAutoloader();
            if (in_array("--db", $this->Commitments->arguments)) {
                new \Tests\Databases;
            } elseif (in_array("--db-indexes", $this->Commitments->arguments)) {
                $indexes = $this->ScanObjectsSchematicsAndIndexes();
                file_put_contents(__path__ . "/src/System/Schematics/indexes.json", json_encode($indexes));
            } else {
                $endpoint   = $this->Commitments->ArgsValues["--endpoint"] ?? null;
                $function   = (!empty($endpoint)? $this->Commitments->ArgsValues["--function"] ?? null: null);
                $crash      = (in_array("--CrashOnFailure", $this->Commitments->arguments)? true: false);
                echo "- - - - - - Providers - - - - -\n";
                $this->TriggeringProvidersTests($crash);
                echo "- - - - - - Endpoints - - - - -\n";
                echo "- - - - - - - - - - - - - - - -\n";
                $this->TriggeringTests($endpoint, $function, (in_array("--debug", $this->Commitments->arguments)? true: false), (in_array("--watch", $this->Commitments->arguments)? true: false), $crash);
            }
        }
        return;
    }

    /**
     * Start providers method control
     * 
     * @param bool $crash
     * 
     * @return void
     */
    private function TriggeringProvidersTests(bool $crash): void {
        $scanned = scandir(__path__ . "/src/Providers");
        foreach ($scanned as $scan) {
            if ($scan !== "." && $scan !== ".." && !is_dir(__path__ . "/src/Providers/{$scan}")) {
                $scan = basename($scan, ".php");
                echo shell_exec("php ./bin/console providers control {$scan}" . ($crash? " --CrashOnFailure": null));
            }
        }
        return;
    }

    /**
     * Triggering test
     * 
     * @param string|null $_endpoint
     * @param string|null $_function
     * @param bool $debug
     * @param bool $watch
     * 
     * @return void
     */
    private function TriggeringTests(?string $_endpoint = null, ?string $_function = null, bool $debug = false, bool $watch = false, bool $crash = false): void {
        if (!$watch) {
            $AppSrc         = scandir(__path__ . "/src/App/");
            $TestsResults   = [];
            foreach ($AppSrc as $App) {
                if ($App !== "." && $App !== "..") {
                    if ($_endpoint === null || strcasecmp($App, $_endpoint) == 0) {
                        $routes = json_decode((file_get_contents(__path__ . "/src/App/{$App}/routes.json") ?? null), false) ?? [];
                        foreach ($routes as $pattern => $route) {
                            [$Class, $Function] = explode("@", $route->service);
                            if ($_function === null || strcasecmp($Function, $_function) == 0) {
                                $Class          = "\\App\\{$App}\\{$Class}";
                                $TestInstance   = new \Tests\TestInstance($Class, $Function);
                                $Response       = new \System\Response($route, __current_version__, true, $TestInstance);
                                try {
                                    $_Class = new $Class();
                                    $_Class->{$Function}($Response, $TestInstance->GetRequiredArgs());
                                    $TestsResults["{$Class}@{$Function}"] = [
                                        "status"    => $TestInstance->IsExpectedContentPresent(),
                                        "provided"  => $TestInstance->DataType,
                                        "expected"  => $TestInstance->DataExpected
                                    ];
                                    $displayStatus  = ($TestsResults["{$Class}@{$Function}"]["status"]? "[\e[32mOK\e[39m]": "[\e[91mERROR\e[39m]");
                                    echo "{$Class}@{$Function}" . ($debug? " >>> (expected: \"$TestInstance->DataExpected\", provided: \"$TestInstance->DataType\")": " -") . " {$displayStatus}\n";
                                    if ($TestsResults["{$Class}@{$Function}"]["status"] === false && $crash) {
                                        exit(1);
                                    }
                                } catch (\Throwable $e) {
                                    echo "[\e[91mERROR\e[39m] - PHP: {$e->getMessage()}\n";
                                }
                            }
                        }
                    }
                }
            }
            return;
        }
        $this->Commitments->WatchoutElement("Listening for file editing...", function () use ($_endpoint, $_function, $debug) {
            $this->TriggeringTests($_endpoint, $_function, $debug, false);
        }, function ($i) {
            $AppSrc = scandir(__path__ . "/src/App/");
            foreach ($AppSrc as $App) {
                if ($App !== "." && $App !== "..") {
                    $scanned = scandir(__path__ . "/src/App/{$App}");
                    foreach ($scanned as $scan) {
                        if ($scan !== "." && $scan !== "..") {
                            $filemtime = filemtime(__path__ . "/src/App/{$App}/{$scan}");
                            if ($filemtime > (time() - 1)) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        });
        return;
    }

    /**
     * Autoload "Tests" class
     * 
     * @return void
     */
    private function LocalTestSplAutoloader(): void {
        spl_autoload_register(function ($class) {
            [ $namespace, $class ] = explode("\\", $class);
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
                            if (isset($Annotation["database"]) && !empty($Annotation["database"]) && isset($Annotation["table"]) && !empty($Annotation["table"])) {
                                echo "- New index found \"{$class}\"\n";
                                $indexes = array_merge($indexes, [ "{$Annotation["table"]}" => $class ]);
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
            $this->Helper->AddHelper("Start test of the features calling in the router", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [
                "Specify an endpoint to test only this one. (ex. --endpoint \"default\")",
                "Specify an function in endpoint to test only this one. (ex. --endpoint \"default\" --function \"about\")"
            ]);
            return true;
        }
        return false;
    }

}