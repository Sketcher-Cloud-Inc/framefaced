<?php

namespace System;

class Routing {

    public object $Routed;
    private object $Arguments;

    public function __construct(
        private string $method,
        private string $uri,
        private object $routes,
        private mixed $PostedDatas = null,
        private bool $DefaultAccessRule
    ){
        $this->Arguments = $this->CreateArgumentsStructure();
        foreach ($this->routes as $Pattern => $Route) {
            if (in_array($this->method, explode("|", $Route->methods))) {
                if ($this->PatternMatched($Pattern)) {
                    $this->Routed               = $Route;
                    $this->Routed->Pattern      = $Pattern;
                    $this->Routed->auth         = (isset($this->Routed->auth)? $this->Routed->auth: $DefaultAccessRule);
                    $this->Routed->arguments    = $this->Arguments;
                }
            }
        }
    }

    /**
     * Parse route pattern
     *
     * @param  string $Pattern
     * @return string
     */
    private function PatternMatched(string $Pattern): bool {
        $Patterns   = \explode("/", trim($Pattern, "/"));
        $Requesteds = \explode("/", trim(((!empty($Requested)? $Requested: $this->uri)), "/"));
        $Binded     = [];

        if (count($Patterns) == count($Requesteds)) {
            foreach ($Patterns as $i=>$Pattern) {
                preg_match("/{(.*)}/", $Pattern, $match);
                if (!empty($match)) {
                    $Patterns[$i]   = $Requesteds[$i];
                    $Binded         = array_merge($Binded, [
                        "{$match[1]}" => $Requesteds[$i]
                    ]);
                }
            }
        }

        $Pattern    = \implode("/", $Patterns) ?? null;
        $Requested  = \implode("/", $Requesteds) ?? null;

        if ($Pattern === $Requested) {
            $this->Arguments->binded = $Binded;
            return true;
        }

        return false;
    }
    
    /**
     * Define arguments structure
     *
     * @return object
     */
    private function CreateArgumentsStructure(): object {
        return (object) [
            "binded" => [],
            "posted" => $this->PostedDatas ?? null
        ];
    }

}