<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\DealLog;
use App\Entity\Depositary;
use App\Enums\ActionEnum;
use App\Repository\DealLogRepository;

class DealLogService
{
    public function __construct(
        private readonly DealLogRepository $dealLogRepository
    ) {
    }

    public function registerDealLog(Application $buyApplication, Application $sellApplication): DealLog
    {
        if ($buyApplication->getAction() === ActionEnum::SELL) {
            return $this->registerDealLog($sellApplication, $buyApplication);
        }

        $dealLog = (new DealLog())
            ->setStock($buyApplication->getStock())
            ->setPrice($buyApplication->getPrice()) // min($buyApplication->getPrice(), $sellApplication->getPrice()) для "комплесных" сделок
            ->setBuyPortfolio($buyApplication->getPortfolio())
            ->setSellPortfolio($sellApplication->getPortfolio())
            ->setQuantity(($buyApplication->getQuantity()))
        ;

        $this->dealLogRepository->saveDealLog($dealLog);

        return $dealLog;
    }

    public function calculateDelta(Depositary $depositary): float
    {
        $sellDealLogs =
            $depositary->getPortfolio()->getSellDealLogs()->filter(
                function (DealLog $sellDealLog) use ($depositary){
                    return $depositary->getStock()->getId() === $sellDealLog->getStock()->getId();
                }
            );

        $buyDealLogs =
            $depositary->getPortfolio()->getBuyDealLogs()->filter(
                function (DealLog $buyDealLog) use ($depositary){
                    return $depositary->getStock()->getId() === $buyDealLog->getStock()->getId();
                }
            );

        $latestDealLog = $this->dealLogRepository->findLatestByStock($depositary->getStock());

        $investSum = 0.0;
        $actualQuantity = 0;

        foreach ($buyDealLogs as $buyDealLog){
            $investSum += $buyDealLog->getQuantity() * $buyDealLog->getPrice();
            $actualQuantity += $buyDealLog->getQuantity();
        }

        foreach ($sellDealLogs as $sellDealLog){
            $investSum -= $sellDealLog->getQuantity() * $sellDealLog->getPrice();
            $actualQuantity -= $sellDealLog->getQuantity();
        }

        $actualSum = $actualQuantity * ($latestDealLog?->getPrice() ?? 0.0);

        return $actualSum - $investSum;
    }
}