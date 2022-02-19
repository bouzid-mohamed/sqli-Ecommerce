<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
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
}
