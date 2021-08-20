<?php

namespace System;

class Authentications {

    public ?object $Rdy;
    private \System\Databases $dbengine;
    private bool $Allowed = false;

    public function __construct(
        private object $Routed,
        private ?string $Token = null
    ) {
        if (!$Routed->auth) {
            $this->Allowed = true;
        } elseif (!empty($Token)) {
            $this->dbengine = new \System\Databases;
            $this->Rdy      = $this->dbengine->Query($_ENV["AUTH_TOKEN_DBNAME"], "SELECT * FROM `{$_ENV["AUTH_TOKEN_TABLE_NAME"]}`;", [], [
                "token" => $this->Token
            ])[0] ?? null;
            if (!empty($this->Rdy)) {
                if (empty($this->Rdy->permissions) || $this->CheckPermissions()) {
                    $this->Allowed = true;
                }
            }
        }
    }

    /**
     * Return true if token is valid for current call
     * 
     * @return bool
     */
    public function isAllowed(): bool {
        return $this->Allowed;
    }

    /**
     * Check if restrictive token is allowed to use current call
     * 
     * @return bool
     */
    private function CheckPermissions(): bool {
        if (in_array("{$_SERVER["REQUEST_METHOD"]}@{$this->Routed->Pattern}", $this->Rdy->permissions) || in_array("*@{$this->Routed->Pattern}", $this->Rdy->permissions)) {
            return true;
        }
        return false;
    }

}