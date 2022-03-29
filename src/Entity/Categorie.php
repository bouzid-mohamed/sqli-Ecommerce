<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CategorieRepository::class)
 */
class Categorie
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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToMany(targetEntity=Categorie::class, mappedBy="catPere", cascade={"persist"})
     */
    private $catFils;

    /**
     * @ORM\ManyToOne(targetEntity=Categorie::class, inversedBy="peres")
     */
    private $catPere;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="entreprise")
     */
    private $entreprise;



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

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|Categorie[]|null
     */
    public function getCatFils(): ?Collection
    {
        return $this->catFils;
    }
    public function getCatPere(): ?Categorie
    {
        return $this->catPere;
    }

    public function setCatFils(?Categorie $c): self
    {
        $this->catFils = $c;

        return $this;
    }
    public function setCatPere(?Categorie $c): self
    {
        $this->catPere = $c;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }
}
