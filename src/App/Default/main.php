<?php

namespace App\Default;

class main extends \System\Routing {
    private ?object $routes;
    private string $namespace = __NAMESPACE__;
    private \System\Response $Response;
    private ?object $Auth;
    public function __construct(
        private string $Version,
        private string $method,
        private string $uri,
        private mixed $PostedDatas,
        private array $headers
    ) {
        $path = realpath(__path__ . "/src/" . str_replace("\\", "/", $this->namespace) . "/routes.json");
        $this->routes = json_decode(file_get_contents($path), false);
        parent::__construct($this->method, $this->uri, $this->routes, $this->PostedDatas, $_ENV["DEFAULT_ACCESS_RULE"]);
        if (!empty($this->Routed)) {
            $Authentications    = new \System\Authentications($this->Routed, $headers["xAuth-Token"] ?? null);
            $this->Auth         = $Authentications?->Rdy ?? null;
            if  ($Authentications->isAllowed()) {
                [$Class, $Function] = explode("@", $this->Routed->service);
                $Class              = "\\{$this->namespace}\\{$Class}";
                $this->Response     = new \System\Response(__CLASS__, $this->Version);
                try {
                    $Class = new $Class();
                    $Class->{$Function}($this->Response, $this->Routed->arguments, $this->Auth);
                } catch (\Throwable $e) {
                    $uuid = (\Symfony\Component\Uid\Uuid::v4())->toRfc4122();
                    (new \System\Logs)->Throw($uuid, 1, $e);
                    $this->Response->Throwable($uuid, $e);
                }
            } else {
                (new \System\Response(__CLASS__, __current_version__))->Throw("ACCESS_DENIED"); 
            }
        } else {
            (new \System\Response(__CLASS__, __current_version__))->Throw("ADDRESS_NOT_FOUND");
        }
    }
}