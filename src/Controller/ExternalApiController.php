<?php

namespace App\Controller;

use App\Model\Deposit;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalApiController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $client)
    {
    }

    #[Route('/external/deposit', name: 'app_external_deposit')]
    public function deposit(): Response
    {
        $cache = new FilesystemAdapter();
        $deposits = $cache->get('max_datetime_deposits', function (ItemInterface $item) {
            $item->expiresAfter(60 * 60 * 24);

            $currentDate = new DateTime();
            $response = $this->client->request(
                'GET',
                "https://www.cbr.ru/dataservice/data?y1={$currentDate->format('Y')}&y2={$currentDate->format('Y')}&publicationId=18&datasetId=37&measureId=2"
            );

            $responseData = $response->toArray();
            $maxDateTime = new DateTime('@0');
            array_walk($responseData['RawData'], function ($item) use (&$maxDateTime) {
                $date = new DateTime($item['date']);
                if ($maxDateTime < $date) {
                    $maxDateTime = $date;
                }
            });

            $maxDateTimeData = array_filter($responseData['RawData'], function ($item) use ($maxDateTime) {
                return $maxDateTime->format('Y-m-d\TH:i:s') === $item['date'];
            });

            $deposits = [];
            foreach ($maxDateTimeData as $data) {
                $deposits[] = new Deposit($data, $responseData['headerData']);
            }

            $item->set($deposits);

            return $deposits;
        });

        // $cache->delete('max_datetime_deposits');

        return $this->render('external/deposit.html.twig', [
            'deposits' => $deposits,
        ]);
    }
}