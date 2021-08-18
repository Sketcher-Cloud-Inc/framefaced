<?php

namespace Console;

class Components {

    /**
     * Component management
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {
            $action         = $this->Commitments->ArgsValues["components"] ?? null;
            $ComponentType  = $this->Commitments->arguments[0] ?? null;
            $ComponentName  = $this->Commitments->ArgsValues[$ComponentType ?? 0] ?? null;
            if (!empty($action) && !empty($ComponentType) && !empty($ComponentName)) {
                if ($action === "create") {
                    $this->CreateNewComponent($ComponentType, $ComponentName);
                    return;   
                } elseif ($action === "delete") {
                    if (strtolower($ComponentName) !== "default") {
                        $this->DeleteComponent($ComponentType, $ComponentName);
                    } else {
                        $this->Commitments->Display("Component \"default\" cannot be deteted.");
                    }
                    return;
                } {
                    $this->Commitments->Display("Unknow action \"{$action}\". Use \"help\" for view informations about this command.");
                    return;
                }
            }
            $this->Commitments->Display("Please read the documentation before type a command. Use \"help\" for view informations about this command.");
            return;
        }
    }
    
    /**
     * Create new component
     *
     * @param  string $type
     * @param  string $name
     * @return void
     */
    private function CreateNewComponent(string $type, string $name): void {
        $type = ucfirst($type);
        $TemplateFiles  = (is_dir(__path__ . "/bin/templates/{$type}")? scandir(__path__ . "/bin/templates/{$type}"): []);
        if (!empty($TemplateFiles)) {
            foreach ($TemplateFiles as $TemplateFile) {
                if ($TemplateFile !== "." && $TemplateFile !== "..") {
                    $FileName   = basename(__path__ . "/bin/templates/{$type}/{$TemplateFile}", ".tmp");
                    $Singled    = ($FileName === '{{__' . strtoupper($type) . '__}}.php'? true: false);
                    $output     = ($Singled? __path__ . "/src/{$type}/" . str_replace('{{__' . strtoupper($type) . '__}}', $name, $FileName): __path__ . "/src/{$type}/{$name}/{$FileName}");
                    if (!$Singled || $Singled && !file_exists($output)) {
                        (!$Singled? (!is_dir(__path__ . "/src/{$type}/{$name}/")? mkdir(__path__ . "/src/{$type}/{$name}/", 0777): null): null);
                        copy(__path__ . "/bin/templates/{$type}/{$TemplateFile}", $output);
                        $FileContent = file_get_contents($output);
                        $FileContent = str_replace('{{__' . strtoupper($type) . '__}}', $name, $FileContent);
                        file_put_contents($output, $FileContent);
                    } else {
                        $this->Commitments->Display("A component named \"{$name}\" already exist.");
                        return;
                    }
                }
            }
            $this->Commitments->Display("Component has been created. Please note, some components require a inclusion in \"composer.json\".");
            return;
        }
        $this->Commitments->Display("Missing templates files for \"{$type}\" !");
        return;
    }

    /**
     * Delete component
     *
     * @param  string $type
     * @param  string $name
     * @return void
     */
    private function DeleteComponent(string $type, string $name): void {
        $type = ucfirst($type);
        $path = __path__ . "/src/{$type}/{$name}/";
        if (is_dir($path) || file_exists(__path__ . "/src/{$type}/{$name}.php")) {
            if ($this->Commitments->AskQuestion("\e[4mAre your sure\e[0m you want to \e[31mdelete this component \e[34m\"{$name}\"\e[39m? (\e[33mThis action is irremediable\e[39m)")) {
                if (!file_exists(__path__ . "/src/{$type}/{$name}.php")) {
                    $scanned = scandir($path);
                    foreach ($scanned as $scan) {
                        if ($scan != "." && $scan !== "..") {
                            unlink("{$path}/{$scan}");
                        }
                    }
                    $err = false;
                    set_error_handler(function(int $errno, string $errstr) use ($type, $name, &$err) {
                        $err = true;
                        $this->Commitments->Display("Cannot delete component \"{$name}\" on \"{$type}\" ! (Error: {$errstr})");
                    }, E_WARNING);
                    rmdir($path);
                    restore_error_handler();
                } else {
                    unlink(__path__ . "/src/{$type}/{$name}.php");
                }
                if (!isset($err) || !$err) {
                    $this->Commitments->Display("Component has been deleted. Please note, some components require a modification on \"composer.json\".");
                }
            }
            return;
        }
        $this->Commitments->Display("Missing component files \"{$name}\" on \"{$type}\" !");
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
            $this->Helper->AddHelper("Component management", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [
                "You can use any types (app, services, ...) of component. Just make sure the component you required is available in \"templates\" folder.",
                "Use \"create\" to add new component. (ex. \"components create app HelloWorld\")",
                "Use \"delete\" to delete a component. (ex. \"components delete app HelloWorld\")"
            ]);
            return true;
        }
        return false;
    }

}