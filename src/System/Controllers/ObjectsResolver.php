<?php

namespace System;

class ObjectsResolver {

    private array $convert;
    
    public function __construct() {
        $this->convert  = [
            "int"           => "integer",
            "bigint"        => "integer",
            "tinyint"       => "bool",
            "float"         => "float",
            "double"        => "double",
            "array"         => "array",
            "multi-array"   => "array"
        ];
    }
        
    /**
     * Resolve object or class
     *
     * @return object
     */
    public function NewResolve(string $ObjectName, array | object $datas): ?object {
        $ObjectName = trim($ObjectName, "\\?/");
        $datas      = (object) $datas ?? (object) [];
        $class      = (substr($ObjectName, 0, 18) !== "System\\Schematics\\"? "System\\Schematics\\{$ObjectName}": $ObjectName);
        $ObjectPath = (__path__ . "/src/" . str_replace("\\", "/", $class) . ".php");
        $dynClass   = str_replace("\\Schematics", "\\DynamicSchematics", $class);
        if ($ObjectPath !== false) {
            $ReflectedClass = new \ReflectionClass($class);
            $dynClass       = new $dynClass;
            $Properties     = $ReflectedClass->getProperties() ?? [];
            foreach ($Properties as $Property) {
                $PropertyName   = $Property->getName();
                $PropertyData   = $datas?->{$Property->getName()} ?? null;
                $NewProperty    = (!$Property->getType()->isBuiltin()? $this->NewResolve($Property->getType(), (!empty($PropertyData)? (gettype($PropertyData) === "string"? json_decode($PropertyData, false): $PropertyData): [])): $PropertyData);
                $PropertyType   = (!is_object($NewProperty)? $this->ParseMySqlTypes($Property->getValue(new $class())): get_class($NewProperty));
                if ($Property->getType()->isBuiltin()) {
                    $NewProperty = ($PropertyType === "array" && gettype($NewProperty) === "string"? json_decode($NewProperty, false) ?? $NewProperty: $NewProperty);
                    settype($NewProperty, $PropertyType);
                }
                $dynClass->{$PropertyName} = (gettype($NewProperty) === $PropertyType || is_object($NewProperty) && $PropertyType === get_class($NewProperty)? (!empty($NewProperty)? $NewProperty: null): null);
            }
            return $dynClass;
        }
        return null;
    }

    /**
     * Convert mysql type to php standard type
     *
     * @param  string $MySqlType
     * @return string
     */
    private function ParseMySqlTypes(string $typed): string {
        $typed = explode("|", $typed);
        foreach ($typed as $type) {
            preg_match('/\((.*)\)/', $type, $value);
            if (!empty($value)) {
                $type = str_replace($value[0], '', $type);
            }
            if (isset($this->convert[$type])) {
                return $this->convert[$type];
            }
        }
        return "string";   
    }

}