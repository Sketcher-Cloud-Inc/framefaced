<?php

namespace System;

use \Symfony\Component\Uid\Uuid;

class Logs {

    private string $Path;

    public function __construct(){
        $this->Path = realpath(__path__ . "/tmp/logs/");
        if (!$this->Path) {
            mkdir(__path__ . "/tmp/logs/", 0777);
            $this->Path = realpath(__path__ . "/tmp/logs/");
        }
    }
    
    /**
     * Throw a new exception in logs
     *
     * @param string $uuid
     * @param int $type (1 = Internal, 2 = External)
     * @param  \Exception/\Throwable $e
     * @return void
     */
    public function Throw(?string $uuid, int $type, \Exception | \Throwable $e): void {
        $type   = ($type === 1? "INTERNAL": ($type === 2? "EXTERNAL": "UNKNOW"));
        $newlog = (object) [
            "uuid"      => (!empty($uuid)? $uuid: (\Symfony\Component\Uid\Uuid::v4())->toRfc4122()),
            "hash"      => md5($e->getMessage()),
            "datetime"  => (new \DateTime())->format(__DATE_FORMAT__),
            "type"      => $type,
            "message"   => $e->getMessage(),
            "trace"     => $e->getTrace()
        ];
        $this->SaveNewLog(strtolower("{$type}.errors"), $newlog);
        return;
    }

    /**
     * Throw a new typed error in logs
     *
     * @param string $uuid
     * @param int $type (1 = Internal, 2 = External)
     * @param  object $e
     * @return void
     */
    public function ThrowTyped(?string $uuid, int $type, object $e): void {
        $type   = ($type === 1? "INTERNAL": ($type === 2? "EXTERNAL": "UNKNOW"));
        $newlog = (object) [
            "uuid"      => (!empty($uuid)? $uuid: (\Symfony\Component\Uid\Uuid::v4())->toRfc4122()),
            "hash"      => md5($e->message),
            "datetime"  => (new \DateTime())->format(__DATE_FORMAT__),
            "type"      => $type,
            "message"   => $e->name,
            "trace"     => "Code: {$e->codename} / Request: ({$_SERVER["REQUEST_METHOD"]}) {$_SERVER["REQUEST_URI"]}"
        ];
        $this->SaveNewLog(strtolower("{$type}.errors"), $newlog);
        return;
    }
    
    /**
     * Save log on tmp files
     *
     * @param string $name
     * @param  object $newlog
     * @return void
     */
    private function SaveNewLog(string $name, object $newlog): void {
        $path   = "{$this->Path}/{$name}.log";
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $content = json_decode($content, true) ?? [];
            array_push($content, $newlog);
            $content = array_values($content);
        } else {
            $content = [ $newlog ];
        }
        file_put_contents($path, json_encode($content, (__debug_mode__? JSON_PRETTY_PRINT: null)));
        return;
    }

}