<?php

namespace App\Default;

class main extends \System\Routing {
    private ?object $routes;
    private string $namespace = __NAMESPACE__;
    private \System\Response $Response;
    public function __construct(
        private string $Version,
        private string $method,
        private string $uri,
        private mixed $PostedDatas
    ) {
        $path = realpath(__path__ . "/src/" . str_replace("\\", "/", $this->namespace) . "/routes.json");
        $this->routes = json_decode(file_get_contents($path), false);
        parent::__construct($this->method, $this->uri, $this->routes, $this->PostedDatas);
        if (!empty($this->Routed)) {
            [$Class, $Function] = explode("@", $this->Routed->service);
            $Class              = "\\{$this->namespace}\\{$Class}";
            $this->Response     = new \System\Response($this->Routed, $this->Version);
            try {
                $Class = new $Class();
                $Class->{$Function}($this->Response, $this->Routed->arguments);
            } catch (\Throwable $e) {
                $uuid = (\Symfony\Component\Uid\Uuid::v4())->toRfc4122();
                (new \System\Logs)->Throw($uuid, 1, $e);
                $this->Response->Throwable($uuid, $e);
            }
        } else {
            (new \System\Response(null, __current_version__))->Throw("ADDRESS_NOT_FOUND");
        }
    }

}