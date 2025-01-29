<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Depositary;
use App\Enums\ActionEnum;
use App\Repository\ApplicationRepository;

class DealService
{
    
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
    ){
        
    }

    public function findAppropriate(Application $application): ?Application
    {
        return $this->applicationRepository->findAppropriate($application);
    }

    public function execute(Application $buyApplication, Application $sellApplication): void
    {
        if ($buyApplication->getAction() === ActionEnum::SELL && $sellApplication->getAction() === ActionEnum::BUY){
            $this->execute($sellApplication, $buyApplication);
            return;
        }

        $buyPortfolio = $buyApplication->getUser()->getPortfolios()->current();
        $sellPortfolio = $sellApplication->getUser()->getPortfolios()->current();

        $sellPortfolio->getDepositaries()->filter(function (Depositary $depositary) use ($buyApplication){
            return $depositary?->getStock()->getId() === $buyApplication?->getStock()->getId();
        })->first();
        
    

        $buyPortfolio
            ->subBalance($sellApplication->getTotal())
            ->addDepositaryQuantityByStockId($sellApplication->getStock(), $sellApplication->getQuantity())
        ;

        $sellPortfolio
            ->addBalance($buyApplication->getTotal())
            ->subDepositaryQuantityByStock($buyApplication->getStock(),$buyApplication->getQuantity())
        ;

        $this->applicationRepository->saveChanges();
    }
}