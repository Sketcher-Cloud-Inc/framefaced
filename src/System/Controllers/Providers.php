<?php

namespace System;

class Providers {

    public array $ProvidersList = [];
    
    public function __construct(
        private ?string $classname = null
    ) {}

    /**
     * Load a provider
     * 
     * @param string $name
     * @param string|null $classname
     * 
     * @return object|null
     */
    public function LoadProvider(string $name, ?string $classname = null): ?object {
        if (isset($this->ProvidersList[$name]) && !empty($this->ProvidersList[$name])) {
            return $this->ProvidersList[$name];
        } else {
            try {
                $class      = "\\{$this->classname}\\{$name}";
                $Provider   = new $class();
            } catch (\Throwable $e) {
                throw new \Exception("[ERROR] Unable to load provider class \"{$name}\" on \"{$class}\"");
            }
            return (!empty($Provider)? $Provider: null);
        }
    }

}