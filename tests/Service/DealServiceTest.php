<?php

namespace App\Tests\Service;

use App\Entity\Depositary;
use App\Entity\Stock;
use App\Entity\Portfolio;
use App\Entity\Application;
use App\Enums\ActionEnum;
use App\Repository\ApplicationRepository;
use App\Repository\DepositaryRepository;
use App\Service\DealLogService;
use App\Service\DealService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DealServiceTest extends TestCase
{
    private ApplicationRepository|MockObject $applicationRepository;
    private DepositaryRepository|MockObject $depositaryRepository;
    private DealLogService|MockObject $dealLogService;
    private DealService $dealService;



    public function setUp(): void
    {
        $this->applicationRepository = $this->createMock(ApplicationRepository::class);
        $this->depositaryRepository = $this->createMock(DepositaryRepository::class);
        $this->dealLogService = $this->createMock(DealLogService::class);

        $this->dealService = new DealService(
            $this->applicationRepository,
            $this->depositaryRepository,
            $this->dealLogService
        );
    }

    /**
     * @dataProvider provideApplications
     */
    public function testExecuteDeal(
        Application|MockObject $originalApplication,
        Application|MockObject|null $appropriateApplication
    ): void
    {
        $this->applicationRepository->expects($this->once())
            ->method('findAppropriate')
            ->with($originalApplication)
            ->willReturn($appropriateApplication)
        ;

        if ($appropriateApplication === null) {
            $this->depositaryRepository->expects($this->never())
                ->method('removeDepositary')
            ;

            $this->applicationRepository->expects($this->never())
                ->method('saveChanges')
            ;

            $this->dealLogService->expects($this->never())
                ->method('registerDealLog')
            ;

            $this->applicationRepository->expects($this->never())
                ->method('removeApplication')
                ->withConsecutive([$originalApplication], [$appropriateApplication])
            ;
        } else {

            $this->applicationRepository->expects($this->once())
                ->method('saveChanges')
            ;

            $this->dealLogService->expects($this->once())
                ->method('registerDealLog')
                ->with($originalApplication, $appropriateApplication)
            ;

            $this->applicationRepository->expects($this->exactly(2))
                ->method('removeApplication')
                ->withConsecutive([$originalApplication], [$appropriateApplication])
            ;
        }

        $this->dealService->executeDeal($originalApplication);
    }


    public function provideApplications(): array
    {
        return [
          "Не получили подходящей заявки" => [
              self::createMock(Application::class),
              null
          ],
          "Есть заявка на покупку и находим на продажу" => [
              self::configureBuyApplication(20,5),
              self::configureSellApplication(20, 5)
          ],
          "Есть заявка на продажу и находим на покупку" => [
              self::configureSellApplication(20, 5),
              self::configureBuyApplication(20, 5)
          ]
        ];
    }

    private function configureBuyApplication(float $price, int $quantity): Application|MockObject
    {
        $buyApplication = self::createMock(Application::class);

        $buyApplication
            ->expects($this->once())
            ->method('getAction')
            ->willReturn(ActionEnum::BUY)
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio = self::createMock(Portfolio::class))
        ;

        $buyApplication
            ->expects($this->exactly(2))
            ->method('getTotal')
            ->willReturn($price * $quantity)
        ;

        $portfolio->expects($this->once())
            ->method('subBalance')
            ->with($price * $quantity)
        ;

        $portfolio->expects($this->once())
            ->method('subFreezeBalance')
            ->with($price * $quantity)
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getStock')
            ->willReturn($stock = self::createMock(Stock::class))
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity)
        ;

        $portfolio->expects($this->once())
            ->method('addDepositaryQuantityByStock')
            ->with($stock, $quantity)
        ;

        return $buyApplication;
    }

    private function configureSellApplication(float $price, int $quantity): Application|MockObject
    {
        $sellApplication = self::createMock(Application::class);

        $sellApplication
            ->expects($this->atMost(1))
            ->method('getAction')
            ->willReturn(ActionEnum::SELL)
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio = self::createMock(Portfolio::class))
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getTotal')
            ->willReturn($price * $quantity)
        ;

        $portfolio->expects($this->once())
            ->method('addBalance')
            ->with($price * $quantity)
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getStock')
            ->willReturn($stock = self::createMock(Stock::class))
        ;


        $portfolio->expects($this->once())
            ->method('getDepositaryByStock')
            ->with($stock)
            ->willReturn($depositary = self::createMock(Depositary::class))
        ;

        $sellApplication
            ->expects($this->exactly(2))
            ->method('getQuantity')
            ->willReturn($quantity)
        ;

        $depositary->expects($this->once())
            ->method('subQuantity')
            ->with($quantity)
        ;

        $depositary->expects($this->once())
            ->method('subFreezeQuantity')
            ->with($quantity)
        ;

        return $sellApplication;
    }


}
