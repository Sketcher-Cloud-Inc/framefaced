<?php

namespace System;

class Authentications {

    private \System\Databases $dbengine;
    private bool $Allowed = false;

    public function __construct(
        private object $Routed,
        public string|object $Token = "unk"
    ) {
        if (!$Routed->auth) {
            $this->Allowed = true;
        } elseif ($Token !== "unk") {
            $this->dbengine = new \System\Databases;
            $this->Token    = $this->dbengine->Query($_ENV["AUTH_TOKEN_DBNAME"], "SELECT * FROM `{$_ENV["AUTH_TOKEN_TABLE_NAME"]}`;", [], [
                "token" => $this->Token
            ])[0] ?? null;
            if (!empty($this->Token)) {
                if (empty($this->Token->permissions) || $this->CheckPermissions()) {
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
        if (in_array("{$_SERVER["REQUEST_METHOD"]}@{$this->Routed->Pattern}", $this->Token->permissions) || in_array("*@{$this->Routed->Pattern}", $this->Token->permissions)) {
            return true;
        }
        return false;
    }

}