<?php


namespace App\Controller;

use App\Entity\Application;
use App\Entity\Stock;
use App\Enums\ActionEnum;
use App\Form\ApplicationType;
use App\Repository\ApplicationRepository;
use App\Service\DealService;
use App\Service\FreezeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/application')]
final class ApplicationController extends AbstractController
{
    public function __construct(
        private readonly FreezeService $freezeService,
        private readonly DealService $dealService,
    ) {
    }

    #[Route(name: 'app_application_index', methods: ['GET'])]
    public function index(ApplicationRepository $applicationRepository): Response
    {
        return $this->render('application/index.html.twig', [
            'applications' => $applicationRepository->findAllByUser($this->getUser()),
        ]);
    }

    #[Route(path: '/glass/{id}', name: 'app_application_glass', methods: ['GET'])]
    public function glass(Stock $stock): Response
    {
        return $this->render('/glass/index.html.twig', [
            'stock' => $stock,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }

    #[Route('/new', name: 'app_application_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $application = new Application();
        $form = $this->createForm(ApplicationType::class, $application, [ 
            'is_edit' => false,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $application->setUser($this->getUser());
            $entityManager->persist($application);
            $this->freezeService->freezeByApplication($application);
            $entityManager->flush();

            $this->dealService->executeDeal($application);

            return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('application/new.html.twig', [
            'application' => $application,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_application_show', methods: ['GET'])]
    public function show(Application $application): Response
    {
        return $this->render('application/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_application_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        $oldQuantity = $application->getQuantity();
        $oldPrice = $application->getPrice();
        $form = $this->createForm(ApplicationType::class, $application, [
            'old_quantity' => $oldQuantity,
            'old_price' => $oldPrice,
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->freezeService->updateFreezeByApplication($application, $oldQuantity, $oldPrice);
            $entityManager->flush();

            $this->dealService->executeDeal($application);

            return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('application/edit.html.twig', [
            'application' => $application,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_application_delete', methods: ['POST'])]
    public function delete(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $application->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($application);
            $this->freezeService->unfreezeByApplication($application);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
    }
}