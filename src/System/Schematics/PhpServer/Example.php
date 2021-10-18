<?php

namespace System\Schematics\PhpServer;

/**
 * Example
 * 
 * @database "example"
 * @collection "phpserver"
 * 
 */
class Example {

    /**
     * Server name (hostname)
     * 
     * @source "datas::RandomValue(localhost, local.dev)"
     * @var string
     */
    public string $SERVER_NAME;

    /**
     * Server port
     * 
     * @source "datas::RandomValue(8080, 80)"
     * @var integer
     */
    public int $SERVER_PORT;

    /**
     * Request method
     * 
     * @source "datas::RandomValue(GET, POST, PUT, DELETE)"
     * @var string
     */
    public string $REQUEST_METHOD;

    /**
     * Request time (milliseconds)
     * 
     * @source "datas::RandomNumber(1229286110, 1829286246)"
     * @var integer
     */
    public int $REQUEST_TIME;

}