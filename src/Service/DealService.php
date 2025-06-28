<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Depositary;
use App\Enums\ActionEnum;
use App\Repository\ApplicationRepository;
use App\Repository\DepositaryRepository;

class DealService
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly DepositaryRepository $depositaryRepository,
        private readonly DealLogService $dealLogService
    ) {
    }

    public function executeDeal(Application $application): void
    {
        $appropriateApplication = $this->applicationRepository->findAppropriate($application);
        if ($appropriateApplication === null) {
            return;
        }

        $this->exchange($application, $appropriateApplication);

        $this->dealLogService->registerDealLog($application, $appropriateApplication);

        $this->applicationRepository->removeApplication($application);
        $this->applicationRepository->removeApplication($appropriateApplication);
    }

    private function exchange(Application $buyApplication, Application $sellApplication): void
    {
        if ($buyApplication->getAction() === ActionEnum::SELL) {
            $this->exchange($sellApplication, $buyApplication);
            return;
        }

        $buyApplication
            ->getPortfolio()
            ->subBalance($buyApplication->getTotal())
            ->subFreezeBalance($buyApplication->getTotal())
            ->addDepositaryQuantityByStock($buyApplication->getStock(), $buyApplication->getQuantity())
        ;

        $sellPortfolio = $sellApplication
            ->getPortfolio()
            //->addBalance($sellApplication->getTotal())
            //->subDepositaryQuantityByStock($sellApplication->getStock(), $sellApplication->getQuantity())
        ;

        // Костыли(
        $sellPortfolio->addBalance($sellApplication->getTotal());
        $sellDepositary = $sellPortfolio->getDepositaryByStock($sellApplication->getStock());
        $sellDepositary
            ->subQuantity($sellApplication->getQuantity())
            ->subFreezeQuantity($sellApplication->getQuantity())
        ;

        if ($sellDepositary->getQuantity() === 0) {
            $this->depositaryRepository->removeDepositary($sellDepositary);
        }
        // Конец костылей

        $this->applicationRepository->saveChanges();
    }
}