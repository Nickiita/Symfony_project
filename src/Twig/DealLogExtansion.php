<?php

namespace App\Twig;

use App\Service\DealLogService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DealLogExtansion extends AbstractExtension
{
    public function __construct(private readonly DealLogService $dealLogService)
    {     
    }

    public function getFunctions(): array
    {
        return[
            new TwigFunction("delta", $this->dealLogService->calculateDelta(...)),
        ];
    }
}