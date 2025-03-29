<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $ticker = null;

    /**
     * @var Collection<int, Application>
     */
    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'stock')]
    private Collection $applications;

    /**
     * @var Collection<int, DealLog>
     */
    #[ORM\OneToMany(targetEntity: DealLog::class, mappedBy: 'stock')]
    private Collection $dealLogs;

    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->dealLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTicker(): ?string
    {
        return $this->ticker;
    }

    public function setTicker(string $ticker): static
    {
        $this->ticker = $ticker;

        return $this;
    }

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Application $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setStock($this);
        }

        return $this;
    }

    public function removeApplication(Application $application): static
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
            if ($application->getStock() === $this) {
                $application->setStock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DealLog>
     */
    public function getDealLogs(): Collection
    {
        return $this->dealLogs;
    }

    public function addDealLog(DealLog $dealLog): static
    {
        if (!$this->dealLogs->contains($dealLog)) {
            $this->dealLogs->add($dealLog);
            $dealLog->setStock($this);
        }

        return $this;
    }

    public function removeDealLog(DealLog $dealLog): static
    {
        if ($this->dealLogs->removeElement($dealLog)) {
            // set the owning side to null (unless already changed)
            if ($dealLog->getStock() === $this) {
                $dealLog->setStock(null);
            }
        }

        return $this;
    }
}
