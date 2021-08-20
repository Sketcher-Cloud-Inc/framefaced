<?php

namespace System;

class Router {

    private ?object $Routes;
    private object $Versions;
    private string $Version;
    private string $InputFormat = "JSON";
    private mixed $InputData;
    private array $headers;

    public function __construct() {
        $this->Versions         = json_decode(file_get_contents(realpath(__path__ . "/src/conf/versions.json")), false) ?? (object) [];
        $this->method           = $_SERVER["REQUEST_METHOD"];
        [$Version, $RequestUri] = $this->GetRealRequestUri($_SERVER["REQUEST_URI"]);
        $this->Version          = $Version;
        $Explore                = $this->ExplorePattern($RequestUri);
        $this->InputData        = $this->ParseInputDatas();
        $this->headers          = getallheaders();
        $this->LoadExploredClass($Explore);
    }
        
    /**
     * Load explored class
     *
     * @param  object $Explore
     * @return void
     */
    private function LoadExploredClass(object $Explore): void {
        $endpoint = str_replace(" ", "", ucwords(str_replace("-", " ", $Explore->endpoint)));
        if (class_exists("\\App\\{$endpoint}\\main")) {
            try {
                $endpoint = "\\App\\{$endpoint}\\main";
                $endpoint = new $endpoint(
                    $this->Version,
                    $this->method,
                    $Explore->uri,
                    $this->InputData,
                    $this->headers
                );
            } catch (\Throwable $e) {
                (new \System\Logs)->Throw(null, 1, $e);
                if (__debug_mode__) {
                    throw $e;
                }
            }
        } else {
            (new \System\Response("\\App\\Default\\main", __current_version__))->Throw("ADDRESS_NOT_FOUND");
        }
        return;
    }

    /**
     * Explore request uri
     *
     * @param  string $Pattern
     * @return object
     */
    private function ExplorePattern(string $Pattern): object {
        $Pattern = trim($Pattern, "/");
        $Pattern = explode("/", $Pattern);
        $Explore = (object) [ 'endpoint' => (!empty($Pattern[0])? $Pattern[0]: "Default") ];
        unset($Pattern[0]);
        $Explore->uri = "/" . implode("/", $Pattern);
        return $Explore;
    }
    
    /**
     * Parse posted datas
     *
     * @return mixed
     */
    private function ParseInputDatas(): mixed {
        $datas = file_get_contents('php://input') ?? null;
        if ($this->InputFormat === "JSON") {
            return json_decode($datas, false) ?? null;
        }
        return null;
    }
    
    /**
     * Get real request uri
     *
     * @param  string $RequestUri
     * @return string
     */
    private function GetRealRequestUri(string $RequestUri): array {
        $parse      = explode("/", trim(trim($RequestUri), "/"));
        preg_match('/v(.*)/', $parse[0], $match);
        $Version    = (!empty($match[0])? $match[0]: $this->Versions->latest->req);
        $RequestUri = str_replace("/{$Version}", '', $RequestUri);
        return [$Version, $RequestUri];
    }

}