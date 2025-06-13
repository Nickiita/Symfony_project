<?php

namespace App\Entity;

use App\Repository\DepositaryRepository;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Twig\Error\RuntimeError;

#[ORM\Entity(repositoryClass: DepositaryRepository::class)]
class Depositary
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stock $stock = null;

    #[ORM\ManyToOne(inversedBy: 'depositaries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Portfolio $portfolio = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $freezeQuantity = 0;

    public function __construct()
    {
        $this->quantity = 0;
        $this->freezeQuantity = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): static
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function addQuantity(int $quantity): static
    {
        $this->quantity += $quantity;

        return $this;
    }

    public function subQuantity(int $quantity): static
    {
        $this->quantity -= $quantity;

        return $this;
    }

    public function getFreezeQuantity(): int
    {
        return $this->freezeQuantity;
    }

    public function setFreezeQuantity(int $freezeQuantity): static
    {
        $this->freezeQuantity = $freezeQuantity;

        return $this;
    }

    public function addFreezeQuantity(int $freezeQuantity): static
    {
        $this->freezeQuantity += $freezeQuantity;

        return $this;
    }

    public function subFreezeQuantity(int $freezeQuantity): static
    {
        $this->freezeQuantity -= $freezeQuantity;

        return $this;
    }

    public function getActualQuantity(): ?int
    {
        return $this->quantity - $this->freezeQuantity;
    }
}
