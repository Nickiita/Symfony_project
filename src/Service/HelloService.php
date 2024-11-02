<?php

namespace App\Service;


class HelloService
{

    private const MIN_LUCKY_NUMBER = 1;
    private const MAX_LUCKY_NUMBER = 3;

    public function generateLuckyNumber(): string
    {
        $number = (string) rand(self::MIN_LUCKY_NUMBER, self::MAX_LUCKY_NUMBER);
        return $number;
    }
}