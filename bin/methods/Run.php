<?php

namespace Console;

class Run {

    private string $path;
    private ?string $host;
    
    /**
     * Start a php development server
     */
    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {
            $this->path         =  __path__ . "/public";
            $this->host         = $this->Commitments->ArgsValues["run"] ?? "localhost:8080";
            $this->OpenIntab    = (in_array("--open", $this->Commitments->arguments)? true: false);

            if (!empty($this->host)) {
                if ($this->OpenIntab) {
                    $this->Commitments->Display("Opening a new Chrome tab ...");
                    pclose(popen("start chrome.exe \"http://{$this->host}\"", 'r'));
                }
                
                $this->Commitments->Display("Run php development server...");
                shell_exec("php -S {$this->host} -t {$this->path}");
                return;
            }

            $this->Commitments->Display("You need to specify hostname and a port.");
        }
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
            $this->Helper->AddHelper("Start a php development server", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [
                "The first argument must be the hostname and port. (ex. \"localhost:8080\")",
                "You can use \"--open\" for open the API in your default browser."
            ]);
            return true;
        }
        return false;
    }

}