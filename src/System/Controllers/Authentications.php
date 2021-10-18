<?php

namespace System;

class Authentications {

    public object $Rdy;
    private bool $Allowed = false;

    public function __construct(
        private object $Routed,
        private ?string $token = null
    ) {
        if (!empty($token)) {
            try {
                $jwt = new \Ahc\Jwt\JWT(__path__ . '/certs/authentications.pem', 'RS384');
                $payload = $jwt->decode($token);
                if ($this->CheckPermissions($payload["permissions"])) {
                    $this->Rdy = (object) $payload;
                    $this->Allowed = true;
                }
            } catch (\Ahc\Jwt\JWTException $e) {
                $failure = (object) [
                    "codename" => "ACCESS_ATTEMPT_FAILURE",
                    "name" => "Access attempt failure",
                    "message" => $e->getMessage(),
                    "code" => 403
                ];
                (new \System\Logs)->ThrowTyped(null, 2, $failure);
                (new \System\Response(__CLASS__, null))->Return($failure, $failure->code);
                exit;
            }
        } elseif (empty($Routed->auth) || $Routed->auth === false) {
            $this->Allowed = true;
        }
    }

    /**
     * Create a new JWT token
     *
     * @param string $access
     * @param array $permissions
     * @return string
     */
    public static function CreateNewJwtToken(string $access, array $permissions = []): string {
        $jwt = new \Ahc\Jwt\JWT(__path__ . '/certs/authentications.pem', 'RS384');
        return $jwt->encode([
            'access'        => $access,
            'permissions'   => $permissions,
            "exp"           => time() + $_ENV["JWT_EXPIRATION_SECONDS"] ?? 86400
        ]);
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
    private function CheckPermissions(array $permissions): bool {
        if (!empty($permissions)) {
            if (in_array("{$_SERVER["REQUEST_METHOD"]}@{$this->Routed->Pattern}", $permissions) || in_array("*@{$this->Routed->Pattern}", $permissions)) {
                return true;
            }
            return false;
        }
        return true;
    }
}