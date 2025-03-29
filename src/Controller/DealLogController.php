<?php
 
namespace App\Controller;
 
use App\Entity\Stock;
use App\Repository\DealLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
 
class DealLogController extends AbstractController
{
    public function __construct(private readonly DealLogRepository $dealLogRepository)
    {
    }
 
    #[Route('/deal/log/{id}', name: 'app_deal_log')]
    public function index(Stock $stock): Response
    {
        $dealLogs = $this->dealLogRepository->findByStock($stock);
 
        return $this->render('deal_log/index.html.twig', [
            'deal_logs' => $dealLogs,
            'stock' => $stock,
        ]);
    }
}