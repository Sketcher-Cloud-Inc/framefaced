<?php

namespace Services;

class Example {

    private \System\ObjectsResolver $ObjectsResolver;

    public function __construct() {
        $this->ObjectsResolver = new \System\ObjectsResolver;
    }

    /**
     * Simple example object
     * 
     * @return array
     */
    public function GetExampleDatas(): object {
        return $this->ObjectsResolver->NewResolve("PhpServer\\Example", $_SERVER);
    }

}

?>