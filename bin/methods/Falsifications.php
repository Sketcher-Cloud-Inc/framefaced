<?php

namespace Console;

class Falsifications {
    

    public function __construct(
        private \Console\Commitments $Commitments,
        private ?\Console\Help &$Helper = null
    ) {
        if (!$this->ShowHelper($this->Helper)) {
            $this->LocalTestSplAutoloader();
            new \Tests\Falsifications(
                (in_array("--entries", $Commitments->arguments)? (int) $Commitments->ArgsValues["--entries"] ?? 10: 10)
            );
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
            [ $namespace, $class ] = explode("\\", $class);
            if ($namespace === "Tests") {
                include __path__ . "/src/Tests/System/{$class}.php";
            }
        });
    }
    
    /**
     * Documentation (used by Help class)
     *
     * @param  Help $Helper
     * @return void
     */
    private function ShowHelper(?\Console\Help &$Helper): bool {
        if (!empty($Helper)) {
            $this->Helper->AddHelper("", strtolower(str_replace(__NAMESPACE__ . "\\", '', __CLASS__)), [

            ]);
            return true;
        }
        return false;
    }

}