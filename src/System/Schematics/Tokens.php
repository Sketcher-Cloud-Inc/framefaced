<?php

namespace System\Schematics;

/**
 * Token
 * 
 * @database "example"
 * @table "tokens"
 * 
 */
class Tokens {

    /**
     * Unique identifier generated on rfc4122 format
     * 
     * @source "datas::NewIdentifier(type: Rfc4122)"
     * @var string
     */
    public string $uuid = "primary|varchar(36)";

    /**
     * Autentication token
     * 
     * @source "datas::NewIdentifier(type: Base58)"
     * @var string
     */
    public string $token = "text(-1)";

    /**
     * Allowed permissions on API
     * 
     * @source "datas::RandomArray(GET@/test, *@/hworld, PUT@/wow)"
     * @var string
     */
    public string $permissions = "array|longtext(-1)|nullable";

}