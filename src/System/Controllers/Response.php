<?php

namespace System;

class Response {

    private string $RespType = "JSON";

    public function __construct(
        private string $Class,
        private ?string $Version,
        private bool $TestMode = false,
        private ?\Tests\TestInstance $TestInstance = null
    ){
        // Check current API version
        if (__current_version__ !== $this->Version) {
            $this->Throw("VERSION_CONTROL_FAILURE");
            exit;
        }
    }
    
    /**
     * Convert and show datas
     *
     * @param  mixed $datas
     * @return void
     */
    public function Return(mixed $datas = null, int $HttpCode = 200): void {
        if (!$this->TestMode) {
            $datas = $this->ParseMixedDatas($datas);
            $this->ShowResponse($datas, $HttpCode);
            return;
        }
        $this->TestInstance->TracedBack($datas);
        return;
    }

    /**
     * Throw a internal message error
     *
     * @param  mixed $ErrorCode
     * @return void
     */
    public function Throw(string $ErrorCode): void {
        $uuid       = (\Symfony\Component\Uid\Uuid::v4())->toRfc4122();
        $ErrorTypes = json_decode(file_get_contents(__path__ . "/src/conf/ErrorsCodes.json"), false) ?? null;
        $ErrorType  = (isset($ErrorTypes->{$ErrorCode}) && !empty($ErrorTypes->{$ErrorCode})? $ErrorTypes->{$ErrorCode}: null);
        if (empty($ErrorType)) {
            $_ErrorTypes = json_decode(file_get_contents(__path__ . "/src/App/" . explode("\\", trim($this->Class, "\\"))[1] . "/ErrorsCodes.json"), false) ?? null;
            $ErrorType  = (isset($_ErrorTypes->{$ErrorCode}) && !empty($_ErrorTypes->{$ErrorCode})? $_ErrorTypes->{$ErrorCode}: $ErrorTypes->UNKNOWN_ERROR);
        }
        $ErrorType->codename = $ErrorCode;
        if (!$this->TestMode) {
            (new \System\Logs)->ThrowTyped($uuid, 2, $ErrorType);
            $this->ShowResponse($this->ParseMixedDatas((object) [
                "uuid"      => $uuid,
                "name"      => $ErrorType->name,
                "message"   => $ErrorType->message,
                "code"      => $ErrorType->code,
            ]), $ErrorType->code);
            return;
        }
        $this->TestInstance->TracedBackError($ErrorCode);
    }
    
    /**
     * Throw a php error
     *
     * @param string $uuid
     * @param  \Throwable/\Exception $e
     * @return void
     */
    public function Throwable(?string $uuid, \Throwable | \Exception $e): void {
        $this->ShowResponse($this->ParseMixedDatas([
            "uuid"      => (!empty($uuid)? $uuid: (\Symfony\Component\Uid\Uuid::v4())->toRfc4122()),
            "name"      => "Internal Server Error",
            "message"   => (!__debug_mode__? "{$e->getMessage()} on {$e->getFile()} at line {$e->getLine()}": "We are unable to disclose further information on the incident. Contact support if the problem persists."),
            "code"      => 500
        ]), 500);
        return;
    }
    
    /**
     * Parse mixed datas
     *
     * @param  mixed $datas
     * @return string
     */
    private function ParseMixedDatas(mixed $datas): ?string {
        if ($this->RespType === "JSON") {
            return json_encode($datas);
        }
        return null;
    }
    
    /**
     * Return response
     *
     * @param  mixed $datas
     * @param  mixed $HttpCode
     * @return void
     */
    private function ShowResponse(string $datas, int $HttpCode = 200): void {
        http_response_code($HttpCode);
        header("Content-Type: Application/{$this->RespType}");
        echo $datas;
        return;
    }

}