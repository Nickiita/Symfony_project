<?php

namespace App\Service;
use App\Repository\HelloRepository;

class HelloService
{
    public function __construct(
        private readonly HelloRepository $helloRepository,
    ) {
    }
    private const MIN_LUCKY_NUMBER = 1;
    private const MAX_LUCKY_NUMBER = 10;

    public function generateLuckyNumber(): string
    {
        $number = rand(self::MIN_LUCKY_NUMBER, self::MAX_LUCKY_NUMBER);
        if ($number % 2 === 0){
            $luckyNumber = $this->helloRepository->createLuckyNumber( (string) $number . 'EVEN');
        }else{
            $luckyNumber = $this->helloRepository->createLuckyNumber( (string) $number . 'DDD');
        }

        return $luckyNumber->getLuckyNumber();
    }
}