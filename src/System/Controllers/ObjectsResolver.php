<?php

namespace System;

class ObjectsResolver {

    /**
     * Resolve object or class
     *
     * @return object
     */
    public function NewResolve(string | \ReflectionClass $object, array | object $data): ?object {
        $object = ($object instanceof \ReflectionClass? $object: new \ReflectionClass($object));
        $data   = (is_array($data)? (object) $data: $data);
        $output = $object->newInstance();
        foreach ($object->getProperties() as $property) {
            $name = $property->getName();
            $type = $property->getType();
            $_data = $data->{$name} ?? null;
            if (!empty($_data)) {
                if ($type->isBuiltin()) {
                    $output->{$name} = $data->{$name};
                } else {
                    if (method_exists($_data, "jsonSerialize")) {
                        $_data = $_data->jsonSerialize();
                        $output->{$name} = $this->NewResolve(trim($type->getName(), "?\\"), $_data);
                    } else {
                        $this->NewException($object->getName(), $name, "Unable to serialize object");
                    }
                }
            } elseif (!$type->allowsNull()) {
                $this->NewException($object->getName(), $name, "Missing a non null index");
            } else {
                $output->{$name} = null;
            }
        }
        return $output;
    }

    /**
     * Show resolver exception
     *
     * @param string $object
     * @param string $index
     * @param string $msg
     * @return void
     */
    private function NewException(string $object, string $index, string $msg): void {
        $debug = debug_backtrace();
        $debug = $debug[count($debug) - 1];
        throw new \Exception("{$msg} ! Object: \"{$object}\", index: \"{$index}\", on {$debug["file"]} at line {$debug["line"]}.");
    }
}