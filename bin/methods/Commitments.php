<?php

namespace Console;

use Closure;

class Commitments {

    public array $ArgsValues = [];
    
    public function __construct(
        public array $arguments
    ){
        unset($this->arguments[0]);
        $this->arguments = array_values($this->arguments);
        $this->arguments = $this->ParseArguments($this->arguments);
        $this->CatchRequestedFunctionality();
    }

    /**
     * Ask question on cli
     * 
     * @param string $question
     * @param bool $bool
     * @param Closure|null $pattern
     * 
     * @return mixed
     */
    public function AskQuestion(string $question, bool $bool = true, ?Closure $pattern = null, string $default = null): mixed {
        while (true) {
            $output = readline("{$question} " . (!empty($default)? "(default: \"{$default}\")": "(required)") . ($bool? " [Y/n]": null) . ":");
            $output = (!empty($output)? ($bool? strtoupper($output): $output): $default);
            if (!empty($output)) {
                if (empty($pattern) || $pattern($output) === true) {
                    if (!$bool || $bool && $output === "Y" || $bool && $output === "N") {
                        $output = ($bool? ($output === "Y"? true: false): $output);
                        break;
                    }
                }
            }
            usleep(5);
        }
        return (isset($output)? $output: null);
    }

    /**
     * Triggering watch loop on function
     * 
     * @param string $message
     * @param callable $func
     * 
     * @return void
     */
    public function WatchoutElement(string $message, callable $func, callable $conditions, int $usleep = 10): void {
        $iterations = 0;
        $spinner    = new \AlecRabbit\Spinner\SnakeSpinner();
        $spinner->begin();
        while (true) {
            $spinner->spin();
            $spinner->message(" {$message}");
            if ($conditions($iterations)) {
                $func();
                sleep(1);
                echo "\n - - - - - - - - - - - - - - - - \n\n";
                $iterations++;
            }
            sleep(($usleep / 1000));
        }
        return;
    }
    
    /**
     * Display message in console
     *
     * @param  string $message
     * @return void
     */
    public function Display(string $message, $ShowDate = true): void {
        if ($ShowDate) {
            $datetime = (new \DateTime())->format("D M d H:i:s Y");
            echo "[{$datetime}] - {$message}\n";
            return;
        }
        echo "- {$message}\n";
        return;
    }
    
    /**
     * Search and execute requested functionality
     *
     * @return void
     */
    private function CatchRequestedFunctionality(): void {
        $func = $this->arguments[0] ?? "help";
        unset($this->arguments[0]);
        $this->arguments = array_values($this->arguments);
        if ($func !== str_replace(__NAMESPACE__ . "\\", '', __CLASS__)) {
            try {
                $func = ucfirst(strtolower($func));
                $func = "\\Console\\{$func}";
                $func = new $func($this);
            } catch (\Throwable $e) {
                $this->Display("\n>>> Fatal uncatched error: {$e->getMessage()}");
            }
        }
        return;
    }

    /**
     * @param array $arguments
     * 
     * @return array
     */
    private function ParseArguments(array $arguments): array {
        foreach ($arguments as $i => &$argument) {
            if (!preg_match('/--(.*)/', $argument, $match) || !preg_match('/-(.*)/', $argument, $match)) {
                if (isset($arguments[($i - 1)]) && !empty($arguments[($i - 1)])) {
                    $this->ArgsValues[$arguments[($i - 1)]] = $argument;
                    unset($arguments[$i]);
                }
            }
        }
        return array_values($arguments);
    }

}