<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EntrepriseRepository::class)
 */
class Entreprise extends User
{

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $gouvernerat;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $delegation;

    /**
     * @ORM\Column(type="integer")
     */
    private $note;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;
    /**
     * @ORM\Column(type="string")
     */
    private $photoAbout = 'default.jpg';

    /**
     * @ORM\Column(type="string", length=7000)
     */
    private $textAbout;

    /**
     * @ORM\OneToMany(targetEntity=Media::class, mappedBy="entreprise")
     */
    private $media;

    public function __construct()
    {
        $this->media = new ArrayCollection();
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

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): self
    {
        $this->note = $note;

        return $this;
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
    public function getTextAbout(): ?string
    {
        return $this->textAbout;
    }

    public function setTextAbout(string $textAbout): self
    {
        $this->textAbout = $textAbout;
        return $this;
    }

    public function getPhotoAbout(): ?string
    {
        return $this->photoAbout;
    }

    public function setPhotoAbout(string $photo): self
    {
        $this->photoAbout = $photo;

        return $this;
    }
}
