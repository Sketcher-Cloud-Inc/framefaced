<?php

namespace System;

class Annotations {

    public array $datas = [];
    private array $phpDoc;

    public function __construct(
        private \ReflectionClass | string $Class,
        private ?string $FunctionName = null
    ) {
        $this->Class = (is_object($this->Class)? $this->Class: (new \ReflectionClass($Class)));
        $this->phpDoc = explode("*", (($this->FunctionName !== null? $this->Class->getMethod($this->FunctionName)->getDocComment(): $this->Class->getDocComment()) ?? null));
        foreach ($this->phpDoc as $phpDoc) {
            if (preg_match('/@(.*) "(.*)"\r\n/', $phpDoc, $match) || preg_match('/@(.*) "(.*)"\n/', $phpDoc, $match) || preg_match('/@(.*) "(.*)"\r/', $phpDoc, $match) || preg_match('/@(.*) "(.*)"\n\r/', $phpDoc, $match)) {
                if (isset($match[1]) && !empty($match[1]) && isset($match[2]) && !empty($match[2])) {
                    $this->datas[$match[1]] = $match[2];
                }
            }
        }
    }
}