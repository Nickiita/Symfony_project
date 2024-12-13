<?php

namespace App\Controller;

use App\Entity\Application;
use App\Enums\ActionEnum;
use App\Form\ApplicationType;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GlassController extends AbstractController
{
    public function __construct(
        private readonly StockRepository $stockRepository,
    ) {

    }

    #[Route('/glass/stock/{stockId}', methods: ['GET'],  name: 'app_stock_glass')]
    public function getStockGlass(int $stockId): Response
    {
        
        //$this->stockRepository->findBy(['id' => $stockId]);
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

    #[Route('/glass/stock/{stockId}', methods: ['POST'], name: 'app_stock_create_application')]
    public function createApplication(int $stockId, Request $request): Response
    {
        $application = new Application();
        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {

        }

        return new Response("hello {$request->getPayload()->getString('action')}");
    }
}
