<?php

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\Depositary;
use App\Entity\Portfolio;
use App\Entity\Stock;
use App\Enums\ActionEnum;
use App\Service\FreezeService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FreezeServiceTest extends TestCase
{
    private FreezeService $freezeService;

    public function setUp(): void
    {
        $this->freezeService = new FreezeService();
    }

    /**
     * @dataProvider provideFreezeCases
     */
    public function testFreezeByApplication(Application|MockObject $application): void
    {
        $this->freezeService->freezeByApplication($application);
    }

    public function provideFreezeCases(): array
    {
        return [
            'BUY' => [self::configureFreezeBuyApplication(20, 5)],
            'SELL' => [self::configureFreezeSellApplication(20, 5, true)],
            'SELL WITH DEPOSITARY NULL' => [self::configureFreezeSellApplication(20, 5, false)]
        ];
    }

    public function configureFreezeBuyApplication(float $price, int $quantity): Application|MockObject
    {
        $buyApplication = self::createMock(Application::class);

        $buyApplication->expects($this->exactly(2))
            ->method('getAction')
            ->willReturn(ActionEnum::BUY);
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio=$this->createMock(Portfolio::class))
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getTotal')
            ->willReturn($price * $quantity)
        ;

        $portfolio
            ->expects($this->once())
            ->method('addFreezeBalance')
            ->with($price * $quantity)
        ;

        return $buyApplication;
    }

    public function configureFreezeSellApplication(float $price, int $quantity, bool $withDepositary): Application|MockObject
    {
        $sellApplication = self::createMock(Application::class);

        $sellApplication
            ->expects($this->once())
            ->method('getAction')
            ->willReturn(ActionEnum::SELL);
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio=$this->createMock(Portfolio::class))
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getStock')
            ->willReturn($stock = $this->createMock(Stock::class))
        ;

        if ($withDepositary) {

            $sellApplication
                ->expects($this->once())
                ->method('getQuantity')
                ->willReturn($quantity)
            ;

            $portfolio
                ->expects($this->once())
                ->method('getDepositaryByStock')
                ->with($stock)
                ->willReturn($depositary = $this->createMock(Depositary::class))
            ;

            $depositary
                ->expects($this->once())
                ->method('addFreezeQuantity')
                ->with($quantity)
            ;
        } else {

            $sellApplication
                ->expects($this->never())
                ->method('getQuantity')
            ;

            $portfolio
                ->expects($this->once())
                ->method('getDepositaryByStock')
                ->with($stock)
                ->willReturn(null)
            ;

            $depositary = $this->createMock(Depositary::class);
            $depositary
                ->expects($this->never())
                ->method('addFreezeQuantity')
            ;
        }

        return $sellApplication;
    }

    /**
     * @dataProvider provideUnFreezeCases
     */
    public function testUnfreezeByApplication(Application|MockObject $application): void
    {
        $this->freezeService->unfreezeByApplication($application);
    }

    public function provideUnfreezeCases(): array
    {
        return [
            'BUY' => [self::configureUnFreezeBuyApplication(20, 5)],
            'SELL' => [self::configureUnFreezeSellApplication(20, 5, true)],
            'SELL WITH DEPOSITARY NULL' => [self::configureUnFreezeSellApplication(20, 5, false)]
        ];
    }

    public function configureUnFreezeBuyApplication(float $price, int $quantity): Application|MockObject
    {
        $buyApplication = self::createMock(Application::class);

        $buyApplication->expects($this->exactly(2))
            ->method('getAction')
            ->willReturn(ActionEnum::BUY);
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio=$this->createMock(Portfolio::class))
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getTotal')
            ->willReturn($price * $quantity)
        ;

        $portfolio
            ->expects($this->once())
            ->method('subFreezeBalance')
            ->with($price * $quantity)
        ;

        return $buyApplication;
    }

    public function configureUnFreezeSellApplication(float $price, int $quantity, bool $withDepositary): Application|MockObject
    {
        $sellApplication = self::createMock(Application::class);

        $sellApplication
            ->expects($this->once())
            ->method('getAction')
            ->willReturn(ActionEnum::SELL);
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio=$this->createMock(Portfolio::class))
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getStock')
            ->willReturn($stock = $this->createMock(Stock::class))
        ;

        if ($withDepositary) {

            $sellApplication
                ->expects($this->once())
                ->method('getQuantity')
                ->willReturn($quantity)
            ;

            $portfolio
                ->expects($this->once())
                ->method('getDepositaryByStock')
                ->with($stock)
                ->willReturn($depositary = $this->createMock(Depositary::class))
            ;

            $depositary
                ->expects($this->once())
                ->method('subFreezeQuantity')
                ->with($quantity)
            ;
        } else {

            $sellApplication
                ->expects($this->never())
                ->method('getQuantity')
            ;

            $portfolio
                ->expects($this->once())
                ->method('getDepositaryByStock')
                ->with($stock)
                ->willReturn(null)
            ;

            $depositary = $this->createMock(Depositary::class);
            $depositary
                ->expects($this->never())
                ->method('subFreezeQuantity')
            ;
        }

        return $sellApplication;
    }

    /**
     * @dataProvider provideUpdateFreezeCases
     */
    public function testUpdateFreezeByApplication(
        float $oldPrice,
        int $oldQuantity,
        float $newPrice,
        int $newQuantity,
        ?bool $withDepositary
    ): void {
        if ($withDepositary === null) {
            $application = $this->configureUpdateFreezeBuyApplication(
                $oldPrice,
                $oldQuantity,
                $newPrice,
                $newQuantity
            );
        } else {
            $application = $this->configureUpdateFreezeSellApplication(
                $oldPrice,
                $oldQuantity,
                $newPrice,
                $newQuantity,
                $withDepositary
            );
        }

        $this->freezeService->updateFreezeByApplication(
            $application,
            $oldQuantity,
            $oldPrice
        );
    }

    public function provideUpdateFreezeCases(): array
    {
        return [
            'BUY UP SUM' => [
                'oldPrice' => 10,
                'oldQuantity' => 5,
                'newPrice' => 20,
                'newQuantity' => 3,
                'withDepositary' => null // депозитарий не нужен для BUY
            ],
            'BUY DOWN SUM' => [
                'oldPrice' => 15,
                'oldQuantity' => 10,
                'newPrice' => 12,
                'newQuantity' => 8,
                'withDepositary' => null // депозитарий не нужен для BUY
            ],
            'SELL WITH DEPOSITARY UP QUANTITY' => [
                'oldPrice' => 10,
                'oldQuantity' => 5,
                'newPrice' => 20,
                'newQuantity' => 8,
                'withDepositary' => true
            ],
            'SELL WITH DEPOSITARY DOWN QUANTITY' => [
                'oldPrice' => 25,
                'oldQuantity' => 15,
                'newPrice' => 30,
                'newQuantity' => 10,
                'withDepositary' => true
            ],
            'SELL WITHOUT DEPOSITARY' => [
                'oldPrice' => 10,
                'oldQuantity' => 5,
                'newPrice' => 20,
                'newQuantity' => 3,
                'withDepositary' => false
            ],
            'SELL CHANGE QUANTITY TO ZERO' => [
                'oldPrice' => 100,
                'oldQuantity' => 50,
                'newPrice' => 120,
                'newQuantity' => 0,
                'withDepositary' => true
            ],
        ];
    }

    public function configureUpdateFreezeBuyApplication(
        float $oldPrice,
        int $oldQuantity,
        float $newPrice,
        int $newQuantity
    ): Application|MockObject
    {
        $oldTotal = $oldPrice * $oldQuantity;
        $newTotal = $newPrice * $newQuantity;

        $buyApplication = self::createMock(Application::class);

        $buyApplication->expects($this->exactly(2))
            ->method('getAction')
            ->willReturn(ActionEnum::BUY);
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio=$this->createMock(Portfolio::class))
        ;

        $buyApplication
            ->expects($this->once())
            ->method('getTotal')
            ->willReturn($newTotal)
        ;

        $portfolio
            ->expects($this->once())
            ->method('subFreezeBalance')
            ->with($oldTotal)
            ->willReturnSelf()
        ;

        $portfolio
            ->expects($this->once())
            ->method('addFreezeBalance')
            ->with($newTotal)
        ;

        return $buyApplication;
    }

    public function configureUpdateFreezeSellApplication(
        float $oldPrice,
        int $oldQuantity,
        float $newPrice,
        int $newQuantity,
        bool $withDepositary
    ): Application|MockObject
    {
        $sellApplication = self::createMock(Application::class);

        $sellApplication
            ->expects($this->once())
            ->method('getAction')
            ->willReturn(ActionEnum::SELL);
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getPortfolio')
            ->willReturn($portfolio=$this->createMock(Portfolio::class))
        ;

        $sellApplication
            ->expects($this->once())
            ->method('getStock')
            ->willReturn($stock = $this->createMock(Stock::class))
        ;

        if ($withDepositary) {

            $sellApplication
                ->expects($this->once())
                ->method('getQuantity')
                ->willReturn($newQuantity)
            ;

            $portfolio
                ->expects($this->once())
                ->method('getDepositaryByStock')
                ->with($stock)
                ->willReturn($depositary = $this->createMock(Depositary::class))
            ;

            $depositary
                ->expects($this->once())
                ->method('subFreezeQuantity')
                ->with($oldQuantity)
                ->willReturnSelf()
            ;

            $depositary
                ->expects($this->once())
                ->method('addFreezeQuantity')
                ->with($newQuantity)
            ;

        } else {

            $sellApplication
                ->expects($this->never())
                ->method('getQuantity')
            ;

            $portfolio
                ->expects($this->once())
                ->method('getDepositaryByStock')
                ->with($stock)
                ->willReturn(null)
            ;

            $depositary = $this->createMock(Depositary::class);
            $depositary
                ->expects($this->never())
                ->method('subFreezeQuantity')
                ->with($oldQuantity)
            ;

            $depositary
                ->expects($this->never())
                ->method('addFreezeQuantity')
                ->with($newQuantity)
            ;
        }

        return $sellApplication;
    }

}
