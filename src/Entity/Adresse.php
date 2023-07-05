<?php

namespace App\Entity;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdresseRepository::class)]
class Adresse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max:99)]
    private ?int $numBatiment = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $rue = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $ville = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $gouvernorat = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Regex("/^\d{4}$/", message: "The value should be a 4-digit number")]
    private ?int $codePostal = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $pays = null;

    #[ORM\ManyToOne(inversedBy: 'adresses')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumBatiment(): ?int
    {
        return $this->numBatiment;
    }

    public function setNumBatiment(int $numBatiment): self
    {
        $this->numBatiment = $numBatiment;

        return $this;
    }

    public function getRue(): ?string
    {
        return $this->rue;
    }

    public function setRue(string $rue): self
    {
        $this->rue = $rue;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getGouvernorat(): ?string
    {
        return $this->gouvernorat;
    }

    public function setGouvernorat(string $gouvernorat): self
    {
        $this->gouvernorat = $gouvernorat;

        return $this;
    }

    public function getCodePostal(): ?int
    {
        return $this->codePostal;
    }

    public function setCodePostal(int $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
