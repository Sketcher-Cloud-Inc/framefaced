<?php

namespace System\Providers;

/**
 * HelloWorld
 * 
 * @methods "ImProvidedContent"
 * 
 */
class HelloWorld extends \System\Providers {

    public function __construct() {
        parent::__construct(__CLASS__);
    }

    public function __call($method, $args): mixed {
        $ProviderName = $args[0] ?? null;
        if (!empty($ProviderName)) {
            $Provider = $this->LoadProvider($ProviderName);
            return $Provider->{$method}();
        }
    }
}