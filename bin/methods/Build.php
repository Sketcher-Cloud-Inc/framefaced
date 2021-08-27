<?php

namespace Console;

class Build {
    
    /**
     * Starting project compilation
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {

            $this->Commitments->Display("Starting project compilation...");
            (is_dir(__path__ . "/dist/")? $this->DeleteDirectory(__path__ . "/dist/"): null);
            mkdir(__path__ . "/dist/", 0777);
            if ($this->Commitments->AskQuestion("Do you want include \".env\" file?")) {
                copy(__path__ . "/.env", __path__ . "/dist/.env");
            }
            echo shell_exec("bash ./bin/scripts/build/build.sh");

            echo "- - - - - - - - - -\n";
            $this->Commitments->Display("Building a docker image...");
            $image = null;
            $image = $this->Commitments->AskQuestion("Enter the name of your docker image (ex. helloworld:1.0.0)", false);
            echo shell_exec("docker build -t {$image} ./");
            return;
        }
        return;
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
     * Documentation (used by Help class)
     *
     * @param  Help $Helper
     * @return void
     */
    private function ShowHelper(?\Console\Help &$Helper): bool {
        if (!empty($Helper)) {
            $this->Helper->AddHelper("Starting project compilation", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [

            ]);
            return true;
        }
        return false;
    }

}