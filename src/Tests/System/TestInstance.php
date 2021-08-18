<?php

namespace Tests;

class TestInstance {

    public string $DataType;
    public string $DataExpected;
    private \ReflectionClass $ReflectedClass;
    
    public function __construct(
        private string $Class,
        private string $Function
    ) {}

    /**
     * Check if content provided is the expected
     *
     * @return bool
     */
    public function IsExpectedContentPresent() {
        $this->DataExpected = trim($this->RetrieveResponseIntent(), "\\");
        if (strcasecmp($this->DataExpected, $this->DataType) == 0 || $this->DataExpected === "void" && $this->DataType === "NULL") {
            return true;
        }
        return false;
    }

    /**
     * Retrieve data type or class name 
     *
     * @param mixed $dump
     * @return void
     */
    public function TracedBack(mixed $dump): void {
        if (is_object($dump)) {
            $this->DataType = get_class($dump) ?? "object";
            $this->DataType = str_replace("DynamicSchematics", "Schematics", $this->DataType);
            return;
        }
        $this->DataType = gettype($dump);
        return;
    }

    /**
     * Retrieve error
     *
     * @param string $ErrorCode
     * @return void
     */
    public function TracedBackError(string $ErrorCode): void {
        $this->DataType = "ErrorCode[{$ErrorCode}]";
        return;
    }
    
    /**
     * Get required arguements
     * 
     * @return mixed
     */
    public function GetRequiredArgs(): mixed {
        $Reflector = $this->GetReflectedClass($this->Class);
        $DocComment = $Reflector->getMethod($this->Function)->getDocComment() ?? null;
        preg_match('/@source (.*)\r\n/', $DocComment, $source);
        if (empty($source[1])) {
            preg_match('/@source (.*)\n/', $DocComment, $source);
            if (empty($source[1])) {
                preg_match('/@source (.*)\r/', $DocComment, $source);
                if (empty($source[1])) {
                    preg_match('/@source (.*)\n\r/', $DocComment, $source);
                }
            }
        }
        $source = $source[1] ?? null;
        if (!empty($source)) {
            $source = str_replace(".", "\\", $source);
            $source = "\\Sources\\{$source}";
            $path   = realpath(realpath(__path__ . "/src/Tests/" . str_replace("\\", "/", $source) . ".php"));
            if ($path !== false) {
                include $path;
                $source = new $source();
                $source = (object) [
                    "binded" => $source?->Binded ?? [],
                    "posted" => $source?->Posted ?? (object) []
                ];
            }
        }
        return $source ?? (object) [ "binded" => [], "posted" => (object) [] ];
    }

    /**
     * Get response intent for doc comment
     * 
     * @return string|null
     */
    private function RetrieveResponseIntent(): ?string {
        $Reflector  = $this->GetReflectedClass($this->Class);
        $DocComment = $Reflector->getMethod($this->Function)->getDocComment() ?? null;
        preg_match('/@return (.*)\r\n/', $DocComment, $return);
        if (empty($return[1])) {
            preg_match('/@return (.*)\n/', $DocComment, $return);
            if (empty($return[1])) {
                preg_match('/@return (.*)\r/', $DocComment, $return);
                if (empty($return[1])) {
                    preg_match('/@return (.*)\n\r/', $DocComment, $return);
                }
            }
        }
        return $return[1] ?? "void";
    }

    /**
     * Create or return reflected class
     * 
     * @param string $Class
     * @return \ReflectionClass
     */
    private function GetReflectedClass(string $Class): \ReflectionClass {
        return (!empty($this->ReflectedClass)? $this->ReflectedClass: new \ReflectionClass($Class));
    }
}