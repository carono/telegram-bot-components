<?php


namespace carono\telegram\abs;


abstract class Button
{
    public static function run($method, $args)
    {
        $action = "action{$method}";
        if (method_exists(static::class, $action)) {
            $object = new static;
            call_user_func_array([$object, $action], $args);
            unset($object);
        }
    }
}