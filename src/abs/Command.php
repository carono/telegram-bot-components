<?php

namespace carono\telegram\abs;

use carono\telegram\Bot;

abstract class Command
{
    abstract public function register(Bot $bot);

    protected function autoRegisterCommand(Bot $bot)
    {
        foreach (get_class_methods($this) as $method) {
            if (str_starts_with($method, 'command')) {
                $bot->hear('/' . strtolower(substr($method, 7)), [$this, $method]);
            }
        }
    }
}