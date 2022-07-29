<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\CommandeRepository;

/**
 * @ORM\Entity(repositoryClass=CommandeRepository::class)
 */
class Commande
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    const STATUS_nouvelle = 'nouvelle';
    const STATUS_confirmationClien = 'confirmationClient';
    const STATUS_confirmationPoste = 'confirmationPoste';
    const STATUS_affectationPoste = 'affectationPoste';
    const STATUS_finie = 'finie';
    const STATUS_annulee = 'annulee';
    const STATUS_affecterLivreur = 'affecterLivreur';
    const STATUS_retour = 'retour';


    /**
     * @ORM\Column(type="string", length=255, columnDefinition="enum('nouvelle', 'confirmationClient','confirmationPoste','affectationPoste','finie','annulee','affecterLivreur','retour')")
     */

    private $status;

    /**
     * @ORM\Column(type="integer")
     */
    private $numTel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $addresse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $gouvernerat;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $delegation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pays;

    /**
     * @ORM\Column(type="float")
     */
    private $prix;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Bon::class, inversedBy="commande")
     */
    private $bon;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="commandeClient")
     */
    private $client;

    /**
     * @ORM\OneToMany(targetEntity=LigneCommande::class, mappedBy="commande", cascade={"persist"})
     */
    private $Lignescommande;

    /**
     * @ORM\ManyToOne(targetEntity=Livreur::class, inversedBy="commandeLivreur")
     */
    private $livreur;





    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, array(self::STATUS_nouvelle, self::STATUS_finie, self::STATUS_confirmationPoste, self::STATUS_confirmationClien, self::STATUS_annulee, self::STATUS_affectationPoste, self::STATUS_retour, self::STATUS_affecterLivreur))) {
            throw new \InvalidArgumentException("Invalid status");
        }
        $this->status = $status;
        return $this;
    }
    public static function getStatusList()
    {
        return array(self::STATUS_nouvelle, self::STATUS_finie, self::STATUS_confirmationPoste, self::STATUS_confirmationClien, self::STATUS_annulee, self::STATUS_affectationPoste, self::STATUS_retour, self::STATUS_affecterLivreur);
    }

    public function getNumTel(): ?int
    {
        return $this->numTel;
    }

    public function setNumTel(int $numTel): self
    {
        $this->numTel = $numTel;

        return $this;
    }

    public function getAddresse(): ?string
    {
        return $this->addresse;
    }

    public function setAddresse(string $addresse): self
    {
        $this->addresse = $addresse;

        return $this;
    }

    public function getGouvernerat(): ?string
    {
        return $this->gouvernerat;
    }

    public function setGouvernerat(string $gouvernerat): self
    {
        $this->gouvernerat = $gouvernerat;

        return $this;
    }

    public function getDelegation(): ?string
    {
        return $this->delegation;
    }

    public function setDelegation(string $delegation): self
    {
        $this->delegation = $delegation;

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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return ($this->createdAt->format('d/m/Y'));
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getBon(): ?Bon
    {
        return $this->bon;
    }

    public function setVoucher(?Bon $bon): self
    {
        $this->bon  = $bon;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client  = $client;

        return $this;
    }

    /**
     * @return Collection|LigneCommande[]
     */
    public function getLignesCommandes(): Collection
    {
        return $this->Lignescommande;
    }

    public function addLigneCommande(LigneCommande $ligneCommande): self
    {
        if (!$this->Lignescommande->contains($ligneCommande)) {
            $this->Lignescommande[] = $ligneCommande;
            $ligneCommande->setCommande($this);
        }

        return $this;
    }

    public function removeLigneCommande(LigneCommande $lignecommande): self
    {
        if ($this->Lignescommande->removeElement($lignecommande)) {
            // set the owning side to null (unless already changed)
            if ($lignecommande->getCommande() === $this) {
                $lignecommande->setCommande(null);
            }
        }

        return $this;
    }

    public function getLivreur(): ?Livreur
    {
        return $this->livreur;
    }

    public function setLivreur(?Livreur $livreur): self
    {
        $this->livreur  = $livreur;
        return $this;
    }
}
