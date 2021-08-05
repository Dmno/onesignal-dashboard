<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProviderRepository::class)
 */
class Account
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $apiKey;

    /**
     * @ORM\OneToMany(targetEntity=App::class, mappedBy="account")
     */
    private $apps;

    public function __construct()
    {
        $this->apps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return Collection|App[]
     */
    public function getApps(): Collection
    {
        return $this->apps;
    }

    public function addApp(App $app): self
    {
        if (!$this->apps->contains($app)) {
            $this->apps[] = $app;
            $app->setAccount($this);
        }

        return $this;
    }

    public function removeApp(App $app): self
    {
        if ($this->apps->contains($app)) {
            $this->apps->removeElement($app);
            // set the owning side to null (unless already changed)
            if ($app->getAccount() === $this) {
                $app->setAccount(null);
            }
        }

        return $this;
    }
}
