<?php

namespace Console;

class Help {
    public function __construct(
        private \Console\Commitments $Commitments
    ) {

        $methods = scandir(__path__ . "/bin/methods/");
        foreach ($methods as $method) {
            if ($method !== "." && $method !== "..") {
                $method = basename($method, ".php");
                if ($method !== "Commitments" && $method !== "Help") {
                    $method = "\\Console\\{$method}";
                    $method = new $method($this->Commitments, $this);
                }
            }
        }
    }
    
    /**
     * Add new section on helper
     *
     * @param  string $name
     * @param  string/array $lines
     * @return void
     */
    public function AddHelper(string $name, string $cmd, array | string $lines): void {
        $lines = (is_array($lines)? "  - " . implode("\n  - ", $lines): $lines);
        $this->Commitments->Display("{$name} [cmd: \"{$cmd}\"]" . "\n{$lines}\n", false);
        return;
    }
}