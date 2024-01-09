<?php


namespace carono\telegram\abs;


use carono\telegram\helpers\StringHelper;

abstract class Model implements \ArrayAccess
{
    private $container;

    public function __get(string $name)
    {
        return $this[$name];
    }

    public function __set(string $name, $value): void
    {
        $this[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this[$name]);
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->container[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * @param $json
     * @return void
     */
    public function load($json)
    {
        if (!empty($json)) {
            return;
        }
        if (is_string($json)) {
            $json = json_decode($json);
        }
        if (is_array($json)) {
            $json = json_decode(json_encode($json));
        }

        foreach ($json as $attribute => $value) {
            if (is_array($value)) {
                $className = str_replace(' ', '', StringHelper::mb_ucwords(preg_replace('/[^\pL\pN]+/u', ' ', $attribute), 'UTF-8'));
                $class = __NAMESPACE__ . "\\" . ucfirst($className);
                if (class_exists($class)) {
                    $model = new $class;
                    $model->load($value);
                    $value = $model;
                }
            }
            $this->$attribute = $value;
        }
    }
}