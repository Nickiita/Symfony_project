<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\DealLog;
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
        ;

        $this->dealLogRepository->saveDealLog($dealLog);

        return $dealLog;
    }
}