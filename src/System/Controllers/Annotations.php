<?php

namespace System;

class Annotations {

    public array $datas = [];

    public function __construct(
        private \ReflectionClass | string $Class,
        private ?string $FunctionName = null,
        private ?string $PropertyName = null
    ) {
        $this->Class = (is_object($this->Class)? $this->Class: (new \ReflectionClass($Class)));
        $phpDocs = (!empty($PropertyName)? $this->Class->getProperty($this->PropertyName)->getDocComment(): (!empty($this->FunctionName)? $this->Class->getMethod($this->FunctionName)->getDocComment(): $this->Class->getDocComment()));
        $phpDocs = (!empty($phpDocs)? explode(PHP_EOL, $phpDocs): []);
        foreach ($phpDocs as $phpDoc) {
            $phpDoc = trim($phpDoc);
            if (preg_match('/\* @(.*)$/', $phpDoc, $match)) {
                $match  = $match[1] ?? null;
                $match  = explode(" ", $match);
                $var    = $match[0];
                unset($match[0]);
                $value  = trim(implode(" ", $match), "\"");
                $this->datas[$var] = $value;
            }
        }
    }
}