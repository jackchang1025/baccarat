<?php

namespace App\Baccarat\Service\Redis;

class RedisProxy extends \Hyperf\Redis\RedisProxy
{
    public function __call($name, $arguments){

        try {

            return parent::__call($name, $arguments);

        } catch (\Exception $e) {

        }

    }
}