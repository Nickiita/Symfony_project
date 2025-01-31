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
    private int $frozenQuantity = 0;

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
        if ($quantity <= 0){
            throw new RuntimeException('Quantity must be greater than 0');
        }

        $this->quantity += $quantity;
        return $this;
    }

    public function subQuantity(int $quantity): static
    {
        if ($this->quantity - $quantity <= 0 || $quantity <= 0){
            throw new RuntimeException('Quantity must be greater than 0');
        }

        $this->quantity -= $quantity;
        return $this;
    }

    public function getFrozenQuantity(): int
    {
        return $this->frozenQuantity;
    }

    public function freezeQuantity(int $quantity): static
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity for freeze must be greater than 0');
        }
    
        if ($this->quantity - $this->frozenQuantity < $quantity) {
            throw new RuntimeException('There are not enough stocks available to freeze');
        }
    
        $this->frozenQuantity += $quantity;
        return $this;
    }
    
    public function unfreezeQuantity(int $quantity): static
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity for unfreeze must be greater than 0');
        }
    
        if ($this->frozenQuantity < $quantity) {
            throw new RuntimeException('Can not unfreeze more than frozen');
        }
    
        $this->frozenQuantity -= $quantity;
        return $this;
    }
}
