<?php

namespace Console;

class Tests {

    private bool $crash;

    /**
     * Start test of the features calling in the router
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {   
            $this->LocalTestSplAutoloader();
            $endpoint       = $this->Commitments->ArgsValues["--endpoint"] ?? null;
            $function       = (!empty($endpoint)? $this->Commitments->ArgsValues["--function"] ?? null: null);
            $this->crash    = (in_array("--CrashOnFailure", $this->Commitments->arguments)? true: false);
            echo "- - - - - - Providers - - - - -\n";
            $this->TriggeringProvidersTests($this->crash);
            echo "- - - - - - Endpoints - - - - -\n";
            echo "- - - - - - - - - - - - - - - -\n";
            $this->TriggeringTests($endpoint, $function, (in_array("--debug", $this->Commitments->arguments)? true: false), (in_array("--watch", $this->Commitments->arguments)? true: false), $this->crash);
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
            $Auth           = $this->GetAuthToken() ?? null;
            $TestsResults   = [];
            foreach ($AppSrc as $App) {
                if ($App !== "." && $App !== "..") {
                    if ($_endpoint === null || strcasecmp($App, $_endpoint) == 0) {
                        $routes = json_decode((file_get_contents(__path__ . "/src/App/{$App}/routes.json") ?? null), false) ?? [];
                        foreach ($routes as $pattern => $route) {
                            [$Class, $Function] = explode("@", $route->service);
                            if ($_function === null || strcasecmp($Function, $_function) == 0) {
                                $Class = "\\App\\{$App}\\{$Class}";
                                if ($this->isTestable($Class, $Function)) {
                                    $TestInstance   = new \Tests\TestInstance($Class, $Function, $this->crash);
                                    $Response       = new \System\Response($Class, __current_version__, true, $TestInstance);
                                    try {
                                        $_Class = new $Class();
                                        $_Class->{$Function}($Response, $TestInstance->GetRequiredArgs($Class, $Function), $Auth);
                                        $TestsResults["{$Class}@{$Function}"] = [
                                            "status"    => $TestInstance->IsExpectedContentPresent(),
                                            "provided"  => $TestInstance->DataType,
                                            "expected"  => $TestInstance->DataExpected
                                        ];
                                        $displayStatus  = ($TestsResults["{$Class}@{$Function}"]["status"]? "[\e[32mOK\e[39m]": "[\e[91mERROR\e[39m]");
                                        echo "{$Class}@{$Function}" . ($debug? " >>> (expected: \"$TestInstance->DataExpected\", provided: \"$TestInstance->DataType\")": " -") . " {$displayStatus}\n";
                                        ($TestsResults["{$Class}@{$Function}"]["status"] === false && $crash? exit(1): null);
                                    } catch (\Throwable $e) {
                                        echo "[\e[91mERROR\e[39m] - PHP: {$e->getMessage()}\n";
                                    }
                                } else {
                                    echo "{$Class}@{$Function} >>> This function is untestable [\e[33mWARNING\e[39m]\n";
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
     * Check if function is testable
     * 
     * @param string $class
     * @param string $function
     * 
     * @return bool
     */
    private function isTestable(string $class, string $function): bool {
        $Annotations = (new \System\Annotations($class, $function))->datas;
        return (!isset($Annotations["untestable"])? true: false);
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
     * Return simple authentication token
     * 
     * @return object|null
     */
    private function GetAuthToken(): ?object {
        $dbengine   = new \System\Databases;
        $tokens     = $dbengine->Query($_ENV["AUTH_TOKEN_DBNAME"], "SELECT * FROM `{$_ENV["AUTH_TOKEN_TABLE_NAME"]}`");
        foreach ($tokens as $i => &$token) {
            // Need to add expiration check here
            break;
        }
        return $tokens[$i] ?? null;
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