<?php

namespace carono\telegram\abs;

use carono\telegram\Bot;

abstract class Command extends \yii\base\Model
{
    abstract public function register(Bot $bot);
}