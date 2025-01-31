<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Portfolio;
use App\Entity\Depositary;
use App\Enums\ActionEnum;
use App\Form\ApplicationType;
use App\Repository\ApplicationRepository;
use App\Repository\StockRepository;
use App\Repository\UserRepository;
use App\Repository\PortfolioRepository;
use App\Service\DealService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GlassController extends AbstractController
{
    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly UserRepository $userRepository,
        private readonly PortfolioRepository $portfolioRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly DealService $dealService
    ) {

    }

    #[Route('glass/{stockId}', methods: ['GET'],  name: 'app_stock_glass')]
    public function getStockGlass(int $stockId): Response
    {
        
        $stock = $this->stockRepository->findById($stockId);
        if ($stock === null){
            throw $this->createNotFoundException("Stock not found");
        }

        return $this->render('glass/stock_glass_index.html.twig', [
            'stock' => $stock,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }

    #[Route('application/create', methods: ['POST'], name: 'app_stock_glass_create_application')]
    public function createApplication(Request $request): Response
    {
        $userId = $request->getPayload()->get('user_id');
        $stockId = $request->getPayload()->get('stock_id');
        $quantity = $request->getPayload()->get('quantity');
        $price = $request->getPayload()->get('price');
        $action = ActionEnum::from($request->getPayload()->get('action'));
        $portfolioId = $request->getPayload()->get('portfolio_id');

        if ($quantity <= 0 || $price <= 0) {
            return new Response("Price and quantity must be greater than 0", Response::HTTP_BAD_REQUEST);
        }

        $stock = $this->stockRepository->findById($stockId);
        $users = $this->userRepository->findBy(['id' => $userId]);
        $portfolio = $this->portfolioRepository->find($portfolioId);
        $user = $this->userRepository->find($userId);

    
        if (!$stock || !$user || !$portfolio) {
            return new Response("Incorrect data", Response::HTTP_BAD_REQUEST);
        }

        $total = $quantity * $price;

        if ($action === ActionEnum::BUY) {
            // Проверка доступного баланса (баланс - замороженные средства)
            $availableBalance = $portfolio->getBalance() - $portfolio->getFrozenBalance();
    
            if ($availableBalance < $total) {
                return new Response("Insufficient funds", Response::HTTP_BAD_REQUEST);
            }
    
            // Заморозка средств
            $portfolio->freezeBalance($total);
        }
    
        if ($action === ActionEnum::SELL) {
            $depositary = $portfolio->getDepositaries()->filter(function (Depositary $d) use ($stock) {
                return $d->getStock()->getId() === $stock->getId();
            })->first();
            
            if (!$depositary instanceof Depositary) {
                return new Response("No depositary found for this stock", Response::HTTP_BAD_REQUEST);
            }

            if ($depositary->getQuantity() - $depositary->getFrozenQuantity() < $quantity) {
                return new Response("Insufficient stocks", Response::HTTP_BAD_REQUEST);
            }
    
            // Заморозка бумаг
            $depositary->freezeQuantity($quantity);
        }


        $application = new Application;
        $application->setStock($stock);
        $application->setQuantity($quantity);
        $application->setAction($action);
        $application->setPrice($price);
        $application->setUser(current($users));
        $application->setPortfolio($portfolio);

        $appropriateApplication = $this->applicationRepository->findAppropriate($application);
        if ($appropriateApplication !== null){
            $this->dealService->execute($application, $appropriateApplication);
        }else{
            $this->applicationRepository->saveApplication($application);
        }

        return new Response("OK", Response::HTTP_CREATED);
    }


    #[Route('application/update', name: 'app_stock_glass_update_application', methods: ['PUT'])]
    public function updateApplication(Request $request): Response
    {
        $applicationId = $request->getPayload()->get('application_id');
        $userId = $request->getPayload()->get('user_id');
        $stockId = $request->getPayload()->get('stock_id');
        $portfolioId = $request->getPayload()->get('portfolio_id');
        
        $newQuantity = (int) $request->getPayload()->get('quantity');
        $newPrice = (float) $request->getPayload()->get('price');

        if ($newQuantity <= 0 || $newPrice <= 0) {
            return new Response("Price and quantity must be greater than 0", Response::HTTP_BAD_REQUEST);
        }

        $application = $this->applicationRepository->find($applicationId);
        $stock = $this->stockRepository->findById($stockId);
        $portfolio = $this->portfolioRepository->find($portfolioId);
        $user = $this->userRepository->find($userId);

        if (!$application || !$stock || !$user || !$portfolio) {
            return new Response("Incorrect data", Response::HTTP_BAD_REQUEST);
        }

        $oldQuantity = $application->getQuantity();
        $oldPrice = $application->getPrice();
        $oldTotal = $oldQuantity * $oldPrice;
        $newTotal = $newQuantity * $newPrice;

        if ($application->getAction() === ActionEnum::BUY) {
            // Учитываем, что текущая заявка уже заморожена, поэтому её нужно сначала разморозить
            $portfolio->unfreezeBalance($oldTotal);
            $availableBalance = $portfolio->getBalance() - $portfolio->getFrozenBalance();

            if ($availableBalance < $newTotal) {
                return new Response("Insufficient funds", Response::HTTP_BAD_REQUEST);
            }
            
            $portfolio->freezeBalance($newTotal);
        } elseif ($application->getAction() === ActionEnum::SELL) {
            $depositary = $portfolio->getDepositaries()->filter(function (Depositary $d) use ($stock) {
                return $d->getStock()->getId() === $stock->getId();
            })->first();

            if (!$depositary instanceof Depositary) {
                return new Response("No depositary found for this stock", Response::HTTP_BAD_REQUEST);
            }

            $depositary->unfreezeQuantity($oldQuantity);
            $availableStocks = $depositary->getQuantity() - $depositary->getFrozenQuantity();

            if ($availableStocks < $newQuantity) {
                return new Response("Insufficient stocks", Response::HTTP_BAD_REQUEST);
            }

            $depositary->freezeQuantity($newQuantity);
        }

        // Обновляем заявку
        $application->setStock($stock);
        $application->setQuantity($newQuantity);
        $application->setPrice($newPrice);
        $application->setUser($user);
        $application->setPortfolio($portfolio);

        $appropriateApplication = $this->applicationRepository->findAppropriate($application);
        if ($appropriateApplication !== null) {
            $this->dealService->execute($application, $appropriateApplication);
        } else {
            $this->applicationRepository->saveApplication($application);
        }

        return new Response("OK", Response::HTTP_ACCEPTED);
    }


    #[Route('application/delete', name: 'app_stock_glass_delete_application', methods: ['DELETE'])]
    public function deleteApplication(Request $request): Response
    {
        $applicationId = $request->getPayload()->get('application_id');
        $application = $this->applicationRepository->find($applicationId);

        if ($application === null) {
            return new Response("Application not found", Response::HTTP_NOT_FOUND);
        }

        $portfolio = $application->getPortfolio();
        $stock = $application->getStock();
        $quantity = $application->getQuantity();
        $price = $application->getPrice();
        $total = $quantity * $price;

        if ($application->getAction() === ActionEnum::BUY) {
            // Размораживание средств при удалении заявки на покупку
            $portfolio->unfreezeBalance($total);
        } elseif ($application->getAction() === ActionEnum::SELL) {
            $depositary = $portfolio->getDepositaries()->filter(function (Depositary $d) use ($stock) {
                return $d->getStock()->getId() === $stock->getId();
            })->first();

            if ($depositary instanceof Depositary) {
                // Размораживание акций при удалении заявки на продажу
                $depositary->unfreezeQuantity($quantity);
            }
        }

        $this->applicationRepository->removeApplication($application);

        return new Response("OK", Response::HTTP_OK);
    }
    
}
