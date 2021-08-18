<?php

namespace Console;

class Update {

    private string $framefaced = "git@github.com:Sketcher-Cloud-Inc/framefaced.git";
    
    /**
     * Update API framwork
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {
            $tag = ($Commitments->ArgsValues["update"] ?? null);
            if ($this->GetVersionByTag($tag)) {
                [ $link, $repo ] = explode("/", $this->framefaced);
                $path            = __path__ . "/.update/" . basename($repo, ".git");
                $scanned         = scandir($path);
                foreach ($scanned as $scan) {
                    if ($scan == "composer.json" || $scan == ".git" || $scan == ".github" || $scan == "README.md" || $scan == "LICENSE" || $scan == ".gitignore" || $scan == ".env.sample") {
                        $this->DeleteDirectory("{$path}/$scan");
                    }
                }

                // Delete default configurations
                $this->DeleteDirectory("{$path}/src/conf/");
                mkdir("{$path}/src/conf", 0777);

                // Delete default Schematics
                $this->DeleteDirectory("{$path}/src/System/Schematics/");
                mkdir("{$path}/src/System/Schematics", 0777);

                // Delete default Services
                $this->DeleteDirectory("{$path}/src/Services/");
                mkdir("{$path}/src/Services", 0777);

                // Delete default App
                $this->DeleteDirectory("{$path}/src/App/");
                mkdir("{$path}/src/App", 0777);

                $applied = $this->ExecuteShell("./bin/scripts/update/apply.sh", [ basename(explode("/", $this->framefaced)[1] ?? "framefaced.git", ".git") ]);
                if ($applied) {
                    $this->Commitments->Display("[\e[32mOK\e[39m] Framework has been updated.");
                    $this->Commitments->Display("Deleting update temporary files...");
                    usleep(200);
                    $this->DeleteDirectory("./.update");
                }
           }
        }
        return;
    }

    /**
     * Get framework version (by tag or latest)
     * 
     * @param string|null $tag
     * 
     * @return bool
     */
    private function GetVersionByTag(?string $tag = null): bool {
        [ $link, $repo ]    = explode("/", $this->framefaced);
        $repo               = basename($repo, ".git");
        $allowed = $this->ExecuteShell("./bin/scripts/update/allowed.sh");
        if ($allowed) {
            (!is_dir(__path__ . "/.git_temp/")? mkdir(__path__ . "/.git_temp/", 0777): null);
            (!is_dir(__path__ . "/.update/")? mkdir(__path__ . "/.update/", 0777): null);
            $cloned = $this->ExecuteShell("./bin/scripts/update/clone.sh", [ $repo, $this->framefaced ]);
            if ($cloned) {
                $latest = $this->ExecuteShell("./bin/scripts/update/latest.sh", [ $repo, $tag ]);
                if ($latest) {
                    return true;
                }
            }
            $this->Commitments->Display("[\e[91mERROR\e[39m] Unable to run upgrade scripts. Check the logs above.");
        } else {
            $this->Commitments->Display("[\e[33mWARNING\e[39m] Framework update is not allowed yet. You need to commit all your changes before start a update.");
        }
        return false;
    }

    /**
     * Delete dir
     * 
     * @param string $dir
     * 
     * @return bool
     */
    private function DeleteDirectory(string $dir): bool {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->DeleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    /**
     * Execute standard shell (bool only)
     * 
     * @param string $script
     * @param array $parameters
     * 
     * @return bool
     */
    private function ExecuteShell(string $script, array $parameters = []): bool {
        $script = "bash {$script}";
        foreach ($parameters as $parameter) {
            $script .= " {$parameter}";
        }
        $output = shell_exec($script);
        if (trim($output, "\n") === "true") {
            return true;
        }
        return false;
    }
    
    /**
     * Documentation (used by Help class)
     *
     * @param  Help $Helper
     * @return void
     */
    private function ShowHelper(?\Console\Help &$Helper): bool {
        if (!empty($Helper)) {
            $this->Helper->AddHelper("Update API framwork", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [

            ]);
            return true;
        }
        return false;
    }

}