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
        return $this->ObjectsResolver->NewResolve("System\\Schematics\\PhpServer\\Example", [
            "SERVER_NAME" => "hello-world",
            "SERVER_PORT" => 1234,
            "REQUEST_METHOD" => "UNKNOW",
            "REQUEST_TIME" => 666
        ]);
    }

}

?>