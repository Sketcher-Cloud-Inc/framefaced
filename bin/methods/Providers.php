<?php

namespace Console;

class Providers {
    
    private object $Provider;
    private bool $crash;
    private array $types = ["mixed", "array", "object", "bool", "string", "float", "int", "interfaces", "callable", "void"];

    /**
     * Manage your providers
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {
            $action         = $Commitments->ArgsValues["providers"] ?? null;
            $ProviderName   = $Commitments->arguments[0] ?? null;
            $this->crash    = (in_array("--CrashOnFailure", $this->Commitments->arguments)? true: false);
            try {
                $Provider           = "\\System\\Providers\\{$ProviderName}";
                $this->Annotations  = (new \System\Annotations($Provider))?->datas["methods"] ?? null;
                $this->Provider     = new $Provider();
            } catch (\Throwable $e) {
                $this->Commitments->Display("[\e[91mERROR\e[39m] Unable to find provider \"{$ProviderName}\" !");
                return;
            }
            if (!empty($this->Provider)) {
                if (!empty($ProviderName) && $action === "control") {
                    $this->ControlProvider($ProviderName, $this->Annotations);
                    return;
                } elseif ($action === "add") {
                    $method = $Commitments->ArgsValues["--method"] ?? null;
                    $return = $Commitments->ArgsValues["--return"] ?? "mixed";
                    $return = (in_array(strtolower(trim($return, "?")), $this->types)? strtolower($return): "\\" . trim($return, "\\"));
                    if (!empty($method)) {
                        if (in_array($return, $this->types) || in_array("?{$return}", $this->types) || class_exists($return)) {
                            $this->AddNewMethodOnProvider($ProviderName, $method, $return);
                            return;
                        }
                        $this->Commitments->Display("[\e[91mERROR\e[39m] Method return (type or class) not found \"{$return}\" !");
                        return;
                    }
                    $this->Commitments->Display("[\e[91mERROR\e[39m] You need to specify a method name !");
                    return;
                } elseif ($action === "create") {
                    $class = $Commitments->ArgsValues["--class"] ?? null;
                    if (!empty($class)) {
                        (!is_dir(__path__ . "/src/Providers/{$ProviderName}")? mkdir(__path__ . "/src/Providers/{$ProviderName}", 0777): null);
                        $output = __path__ . "/src/Providers/{$ProviderName}/{$class}.php";
                        copy(__path__ . "/bin/templates/Providers/Class/Model.php.tmp", $output);
                        $FileContent = file_get_contents($output);
                        $FileContent = str_replace("{{__PROVIDERS__}}", $ProviderName, $FileContent);
                        $FileContent = str_replace("{{__MODELS__}}", $class, $FileContent);
                        file_put_contents($output, $FileContent);
                        $this->Commitments->Display("[\e[32mSUCCESS\e[39m] Class has been implemented in provider \"{$ProviderName}\".");
                        return;
                    }
                    $this->Commitments->Display("[\e[91mERROR\e[39m] You need to specify a class name !");
                    return;
                }
            }
        }
        return;
    }

    /**
     * Check if all functions exist in all provider class
     * 
     * @param string $ProviderName
     * @param string|null $_Annotations
     * 
     * @return void
     */
    private function ControlProvider(string $ProviderName, ?string $_Annotations): void {
        $Annotations = [];
        foreach (explode(",", $_Annotations) as $Annotation) {
            $Annotations[trim($Annotation)] = false;
        }
        if (is_dir(__path__ . "/src/Providers/{$ProviderName}")) {
            foreach (scandir(__path__ . "/src/Providers/{$ProviderName}") as $scan) {
                if ($scan !== "." && $scan !== "..") {
                    $scan           = basename($scan, ".php");
                    $ReflectedClass = new \ReflectionClass("\\System\\Providers\\{$ProviderName}\\{$scan}");
                    $methods        = $ReflectedClass->getMethods() ?? [];
                    foreach ($methods as $method) {
                        $method = $method->getName();
                        if (in_array($method, array_keys($Annotations))) {
                            $Annotations[$method] = true;
                        }
                    }
                    echo "- - - - - - - - - - - - - - - -\n";
                    $this->Commitments->Display((in_array(false, $Annotations)? "[\e[91mERROR\e[39m]": "[\e[32mSUCCESS\e[39m]") . " Mismatch function(s) in provider \"{$ProviderName} >> {$scan}\": !");
                    foreach ($Annotations as $function => $status) {
                        $this->Commitments->Display(">>> \"{$function}\" " . ($status? "[\e[32mOK\e[39m]": "[\e[91mMISSING\e[39m]"));
                        ($this->crash? exit(1): null);
                    }
                }
            }
        } else {
            $this->Commitments->Display("[\e[33mWARNING\e[39m] No method found for \"{$ProviderName}\" !");
        }
    }

    /**
     * Add new method on provider (list and create blank function)
     * 
     * @param string $ProviderName
     * @param string $method
     * @param string $return
     * 
     * @return void
     */
    private function AddNewMethodOnProvider(string $ProviderName, string $method, string $return): void {
        $AnnoStr        = "@methods \"{$this->Annotations}\"";
        $Annotations    = explode(",", $this->Annotations);
        $Annotations    = (!empty($Annotations[0])? $Annotations: []);
        foreach ($Annotations as &$Annotation) {
            $Annotation = trim($Annotation);
        }
        if (!in_array($method, $Annotations)) {
            (!is_dir(__path__ . "/src/Providers/{$ProviderName}")? mkdir(__path__ . "/src/Providers/{$ProviderName}", 0777): null);
            array_push($Annotations, $method);
            $Annotations    = implode(", ", $Annotations);
            $path           = __path__ . "/src/Providers/{$ProviderName}.php";
            $FileContent    = file_get_contents($path) ?? null;
            $FileContent    = str_replace($AnnoStr, "@methods \"{$Annotations}\"", file_get_contents($path) ?? null);
            file_put_contents($path, $FileContent);
            $this->Commitments->Display("[\e[32mSUCCESS\e[39m] Method has been implemented in provider interface.");
            return;
        }
        $this->Commitments->Display("[\e[91mERROR\e[39m] This method already exist !");
        return;
    }
    
    /**
     * Documentation (used by Help class)
     *
     * @param  Help $Helper
     * @return void
     */
    private function ShowHelper(?\Console\Help &$Helper): bool {
        if (!empty($Helper)) {
            $this->Helper->AddHelper("Manage your providers", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [

            ]);
            return true;
        }
        return false;
    }

}