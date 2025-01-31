<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Portfolio;
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

        $stock = $this->stockRepository->findById($stockId);
        $users = $this->userRepository->findBy(['id' => $userId]);
        $portfolio = $this->portfolioRepository->find($portfolioId);

        if ($stock === null) {
            throw $this->createNotFoundException("Stock not found");
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw $this->createNotFoundException("User not found");
        }

        if ($portfolio === null) {
            throw $this->createNotFoundException("Portfolio not found");
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
        $quantity = $request->getPayload()->get('quantity');
        $price = $request->getPayload()->get('price');
        $action = ActionEnum::from($request->getPayload()->get('action'));
        $portfolioId = $request->getPayload()->get('portfolio_id');

        $stock = $this->stockRepository->findById($stockId);
        $users = $this->userRepository->findBy(['id' => $userId]);
        $portfolio = $this->portfolioRepository->find($portfolioId);
        $application = $this->applicationRepository->find($applicationId);

        if ($application === null){
            throw $this->createNotFoundException("Application not found");
        }

        if ($stock === null) {
            throw $this->createNotFoundException("Stock not found");
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw $this->createNotFoundException("User not found");
        }

        if ($portfolio === null) {
            throw $this->createNotFoundException("Portfolio not found");
        }

        $application->setStock($stock);
        $application->setQuantity($quantity);
        $application->setAction($action);
        $application->setPrice($price);
        $application->setUser(current($users));
        $application->setPortfolio($portfolio);

        $appropriateApplication = $this->applicationRepository->findAppropriate($application);
        if($appropriateApplication !== null){
            $this->dealService->execute($application, $appropriateApplication);
        }else{
            $this->applicationRepository->saveApplication($application);
        }

        
        return new Response("OK", Response::HTTP_ACCEPTED);
    }

    #[Route('application/delete', name: 'app_stock_glass_delete_application', methods: ['DELETE'])]
    public function deleteApplication(Request $request): Response
    {
        $applicationId = $request->getPayload()->get('application_id');
        $application = $this->applicationRepository->find($applicationId);

        if ($application === null){
            throw $this->createNotFoundException("Application not found");
        }

        $this->applicationRepository->removeApplication($application);

        return new Response("OK", Response::HTTP_OK);
    }

}
