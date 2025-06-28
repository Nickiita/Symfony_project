<?php
 
namespace App\Service;
 
use App\Entity\Application;
use App\Enums\ActionEnum;
 
class FreezeService
{
    public function freezeByApplication(Application $application): void
    {
        if ($application->getAction() === ActionEnum::SELL) {
            $depositaryToSell = $application->getPortfolio()->getDepositaryByStock($application->getStock());
            $depositaryToSell?->addFreezeQuantity($application->getQuantity());
        } elseif ($application->getAction() === ActionEnum::BUY) {
            $application
                ->getPortfolio()
                ->addFreezeBalance($application->getTotal())
            ;
        }
    }
 
    public function updateFreezeByApplication(Application $application, int $oldQuantity, float $oldPrice): void
    {
        if ($application->getAction() === ActionEnum::SELL) {
            $depositaryToSell = $application->getPortfolio()->getDepositaryByStock($application->getStock());
            $depositaryToSell
                ?->subFreezeQuantity($oldQuantity)
                ?->addFreezeQuantity($application->getQuantity())
            ;
        } elseif ($application->getAction() === ActionEnum::BUY) {
            $application
                ->getPortfolio()
                ->subFreezeBalance($oldPrice * $oldQuantity)
                ->addFreezeBalance($application->getTotal())
            ;
        }
    }

    public function unfreezeByApplication(Application $application): void
    {
        if ($application->getAction() === ActionEnum::SELL) {
            $depositaryToSell = $application->getPortfolio()->getDepositaryByStock($application->getStock());
            $depositaryToSell?->subFreezeQuantity($application->getQuantity());
        } elseif ($application->getAction() === ActionEnum::BUY) {
            $application
                ->getPortfolio()
                ->subFreezeBalance($application->getTotal());
        }
    }
}