<?php

namespace App\Default;

class Index {

    private \Services\Example $Example;

    public function __construct() {
        $this->Example = new \Services\Example;
    }

    /**
     * Simple test function
     *
     * @param \System\Response $Response
     * @param object $Arguments
     * @return System\Schematics\PhpServer\Example
     */
    public function About(\System\Response $Response, object $Arguments): void {
        $Response->Return($this->Example->GetExampleDatas());
    }

}