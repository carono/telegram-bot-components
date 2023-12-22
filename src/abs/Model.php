<?php


namespace carono\telegram\abs;


abstract class Model implements \ArrayAccess
{
    private $container;

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->container[$offset] ?? null;
    }

    public function load($json)
    {
        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        if (!empty($json)) {
            foreach ($json as $attribute => $value) {
                if (is_array($value)) {
                    $className = str_replace(' ', '', static::mb_ucwords(preg_replace('/[^\pL\pN]+/u', ' ', $attribute), 'UTF-8'));
                    $class = __NAMESPACE__ . "\\" . ucfirst($className);
                    if (class_exists($class)) {
                        $model = new $class;
                        $model->load($value);
                        $value = $model;
                    }
                }
                $this->{$attribute} = $value;
            }
        }
    }
}