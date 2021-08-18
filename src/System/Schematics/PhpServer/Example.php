<?php

namespace System\Schematics\PhpServer;

/**
 * Example
 * 
 * @database "example"
 * @table "phpserver"
 * 
 */
class Example {

    /**
     * Server name (hostname)
     * 
     * @source "datas::RandomValue(localhost, local.dev)"
     * @var string
     */
    public string $SERVER_NAME = "varchar(255)";

    /**
     * Server port
     * 
     * @source "datas::RandomValue(8080, 80)"
     * @var string
     */
    public string $SERVER_PORT = "varchar(255)";

    /**
     * Request method
     * 
     * @source "datas::RandomValue(GET, POST, PUT, DELETE)"
     * @var int
     */
    public string $REQUEST_METHOD = "varchar(255)";

    /**
     * Request time (milliseconds)
     * 
     * @source "datas::RandomNumber(1229286110, 1829286246)"
     * @var int
     */
    public string $REQUEST_TIME = "bigint(12)";

}