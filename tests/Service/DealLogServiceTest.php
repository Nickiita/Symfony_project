<?php

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\DealLog;
use App\Entity\Depositary;
use App\Entity\Portfolio;
use App\Entity\Stock;
use App\Enums\ActionEnum;
use App\Repository\DealLogRepository;
use App\Service\DealLogService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DealLogServiceTest extends TestCase
{
    private DealLogService $dealLogService;
    private DealLogRepository|MockObject $dealLogRepository;

    protected function setUp(): void
    {
        $this->dealLogRepository = $this->createMock(DealLogRepository::class);
        $this->dealLogService = new DealLogService($this->dealLogRepository);
    }

    /**
     * @dataProvider provideRegisterDealLogCases
     */
    public function testRegisterDealLog(Application $buyApp, Application $sellApp, float $expectedPrice, int $expectedQuantity): void
    {
        $this->dealLogRepository
            ->expects($this->once())
            ->method('saveDealLog')
            ->with($this->callback(function(DealLog $dealLog) use ($buyApp, $sellApp, $expectedPrice, $expectedQuantity) {
                return $dealLog->getStock() === $buyApp->getStock()
                    && $dealLog->getPrice() === $expectedPrice
                    && $dealLog->getQuantity() === $expectedQuantity
                    && $dealLog->getBuyPortfolio() === $buyApp->getPortfolio()
                    && $dealLog->getSellPortfolio() === $sellApp->getPortfolio();
            }));

        $result = $this->dealLogService->registerDealLog($buyApp, $sellApp);

        $this->assertSame($expectedPrice, $result->getPrice());
        $this->assertSame($expectedQuantity, $result->getQuantity());
        $this->assertSame($buyApp->getStock(), $result->getStock());
        $this->assertSame($buyApp->getPortfolio(), $result->getBuyPortfolio());
        $this->assertSame($sellApp->getPortfolio(), $result->getSellPortfolio());
    }

    public function provideRegisterDealLogCases(): array
    {
        return [
            'BUY THEN SELL'  => $this->configureRegisterCase(ActionEnum::BUY, ActionEnum::SELL, 100, 5),
            'SELL THEN BUY'  => $this->configureRegisterCase(ActionEnum::SELL, ActionEnum::BUY, 50, 3),
        ];
    }

    private function configureRegisterCase(ActionEnum $firstAction, ActionEnum $secondAction, float $price, int $quantity): array
    {
        $first = $this->createMock(Application::class);
        $second = $this->createMock(Application::class);
        $stock = $this->createMock(Stock::class);
        $buyPortfolio = $this->createMock(Portfolio::class);
        $sellPortfolio = $this->createMock(Portfolio::class);

        $first->method('getAction')->willReturn($firstAction);
        $second->method('getAction')->willReturn($secondAction);

        if ($firstAction === ActionEnum::BUY) {
            $buyApp = $first;
            $sellApp = $second;
        } else {
            $buyApp = $second;
            $sellApp = $first;
        }


        $buyApp->method('getAction')->willReturn(ActionEnum::BUY);
        $buyApp->method('getStock')->willReturn($stock);
        $buyApp->method('getPrice')->willReturn($price);
        $buyApp->method('getQuantity')->willReturn($quantity);
        $buyApp->method('getPortfolio')->willReturn($buyPortfolio);


        $sellApp->method('getAction')->willReturn(ActionEnum::SELL);
        $sellApp->method('getPortfolio')->willReturn($sellPortfolio);

        return [$buyApp, $sellApp, $price, $quantity];
    }

    /**
     * @dataProvider provideCalculateDeltaCases
     */
    public function testCalculateDelta(array $buyData, array $sellData, float $latestPrice, float $expectedDelta): void
    {

        $stock = $this->createMock(Stock::class);
        $stock->method('getId')->willReturn(1);

        $depositary = $this->createMock(Depositary::class);
        $depositary->method('getStock')->willReturn($stock);


        $buyLogs = [];
        foreach ($buyData as $data) {
            $log = new DealLog();
            $log->setStock($stock)->setPrice($data['price'])->setQuantity($data['quantity']);
            $buyLogs[] = $log;
        }
        $sellLogs = [];
        foreach ($sellData as $data) {
            $log = new DealLog();
            $log->setStock($stock)->setPrice($data['price'])->setQuantity($data['quantity']);
            $sellLogs[] = $log;
        }

        $portfolio = $this->createMock(Portfolio::class);
        $depositary->method('getPortfolio')->willReturn($portfolio);
        $portfolio->method('getBuyDealLogs')->willReturn(new ArrayCollection($buyLogs));
        $portfolio->method('getSellDealLogs')->willReturn(new ArrayCollection($sellLogs));

        // Mock repository latest price
        $latestLog = new DealLog();
        $latestLog->setStock($stock)->setPrice($latestPrice)->setQuantity(1);
        $this->dealLogRepository
            ->expects($this->once())
            ->method('findLatestByStock')
            ->with($stock)
            ->willReturn($latestLog);

        $delta = $this->dealLogService->calculateDelta($depositary);
        $this->assertEquals($expectedDelta, $delta);
    }

    public function provideCalculateDeltaCases(): array
    {
        return [
            'mixed buy and sell' => [
                // buyData
                [ ['price' => 10, 'quantity' => 10], ['price' => 20, 'quantity' => 5] ],
                // sellData
                [ ['price' => 15, 'quantity' => 8] ],
                // latestPrice
                25,
                // expectedDelta
                95
            ],
        ];
    }
}
