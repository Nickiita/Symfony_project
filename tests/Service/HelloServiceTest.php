<?php

namespace App\Tests\Service;

use App\Repository\HelloRepository;
use App\Service\HelloService;
use App\Entity\Hello;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HelloServiceTest extends TestCase
{
    private HelloRepository|MockObject $helloRepository;
    private HelloService $helloService;

    protected function setUp(): void
    {
        $this->helloRepository = $this->createMock(HelloRepository::class);

        $this->helloService = new HelloService(
            $this->helloRepository
        );
          
    }

    /**
    * @dataProvider provideLuckyNumbers
    */
    public function testGenerateLuckyNumber(string $expectedLuckyNumber): void
    {

        $helloObject = $this->createMock(Hello::class);
        $helloObject
            ->expects($this->once())
            ->method('getLuckyNumber')
            ->willReturn($expectedLuckyNumber)
        ;

        $this->helloRepository
            ->expects($this->once())
            ->method('createLuckyNumber')
            ->willReturn($helloObject)
        ;
        
        $actualLuckyNumber = $this->helloService->generateLuckyNumber();
        $this->assertEquals(
            $expectedLuckyNumber,
            $actualLuckyNumber
        );
    }

    public static function provideLuckyNumbers(): array
    {
        return[
            'Один' => ['1'],
            'Два' => ['2'],
            'Три' => ['3']
        ];
    }
}
