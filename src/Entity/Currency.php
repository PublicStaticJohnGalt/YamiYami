<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CurrencyRepository::class)
 */
class Currency
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $externalId;

    /**
     * @ORM\Column(type="integer")
     */
    private $numCode;

    /**
     * @ORM\Column(type="string", length=3)
     */
    private $charCode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=CurrencyRate::class, mappedBy="currencyId", orphanRemoval=true)
     */
    private $currencyRates;

    public function __construct()
    {
        $this->currencyRates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getNumCode(): ?int
    {
        return $this->numCode;
    }

    public function setNumCode(int $numCode): self
    {
        $this->numCode = $numCode;

        return $this;
    }

    public function getCharCode(): ?string
    {
        return $this->charCode;
    }

    public function setCharCode(string $charCode): self
    {
        $this->charCode = $charCode;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|CurrencyRate[]
     */
    public function getCurrencyRates(): Collection
    {
        return $this->currencyRates;
    }

    public function addCurrencyRate(CurrencyRate $currencyRate): self
    {
        if (!$this->currencyRates->contains($currencyRate)) {
            $this->currencyRates[] = $currencyRate;
            $currencyRate->setCurrencyId($this);
        }

        return $this;
    }

    public function removeCurrencyRate(CurrencyRate $currencyRate): self
    {
        if ($this->currencyRates->removeElement($currencyRate)) {
            // set the owning side to null (unless already changed)
            if ($currencyRate->getCurrencyId() === $this) {
                $currencyRate->setCurrencyId(null);
            }
        }

        return $this;
    }

}
