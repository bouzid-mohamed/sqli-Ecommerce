<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @ORM\Entity(repositoryClass=PromotionRepository::class)
 */
class Promotion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $banniere;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateDebut;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateFin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $pourcentage;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="entreprises")
     */
    private $entreprise;

    /**
     * @ORM\OneToMany(targetEntity="Produit", mappedBy="promotion", cascade={"persist", "remove"})
     */
    private $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getBanniere(): ?string
    {
        return $this->banniere;
    }

    public function setBanniere(string $banniere): self
    {
        $this->banniere = $banniere;

        return $this;
    }

    public function getDateDebut(): ?string
    {
        return ($this->dateDebut->format('d/m/Y'));
    }

    public function setDateDebut(\DateTime $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?string
    {
        return $this->dateFin->format('d/m/Y');
    }

    public function setDateFin(\DateTime $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getPourcentage(): ?int
    {
        return $this->pourcentage;
    }

    public function setPourcentage(int $pourcentage): self
    {
        $this->pourcentage = $pourcentage;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $e): self
    {
        $this->entreprise = $e;

        return $this;
    }

    public function addProduit(Produit $produit): self
    {
        $this->produits[] = $produit;

        return $this;
    }

    public function removeCountry(Produit $produit): bool
    {
        return $this->produits->removeElement($produit);
    }

    public function getProduits(): Collection
    {
        return $this->produits;
    }
}
