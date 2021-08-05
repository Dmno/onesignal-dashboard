<?php

namespace App\Entity;

use App\Repository\AppRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AppRepository::class)
 */
class App
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
    private $appId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $domain;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalUsers;

    /**
     * @ORM\Column(type="integer")
     */
    private $subscribedUsers;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $authKey;

    /**
     * @ORM\Column(type="integer")
     */
    private $increase = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastCheck;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="apps")
     */
    private $account;

    /**
     * @ORM\OneToMany(targetEntity=NotificationData::class, mappedBy="app")
     */
    private $notificationData;

    public function __construct()
    {
        $this->notificationData = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): self
    {
        $this->appId = $appId;

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

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getTotalUsers(): ?int
    {
        return $this->totalUsers;
    }

    public function setTotalUsers(int $totalUsers): self
    {
        $this->totalUsers = $totalUsers;

        return $this;
    }

    public function getSubscribedUsers(): ?int
    {
        return $this->subscribedUsers;
    }

    public function setSubscribedUsers(int $subscribedUsers): self
    {
        $this->subscribedUsers = $subscribedUsers;

        return $this;
    }

    public function getAuthKey(): ?string
    {
        return $this->authKey;
    }

    public function setAuthKey(string $authKey): self
    {
        $this->authKey = $authKey;

        return $this;
    }

    public function getIncrease(): ?int
    {
        return $this->increase;
    }

    public function setIncrease(int $increase): self
    {
        $this->increase = $increase;

        return $this;
    }

    public function getLastCheck(): ?\DateTimeInterface
    {
        return $this->lastCheck;
    }

    public function setLastCheck(\DateTimeInterface $lastCheck): self
    {
        $this->lastCheck = $lastCheck;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return Collection|NotificationData[]
     */
    public function getNotificationData(): Collection
    {
        return $this->notificationData;
    }

    public function addNotificationData(NotificationData $notificationData): self
    {
        if (!$this->notificationData->contains($notificationData)) {
            $this->notificationData[] = $notificationData;
            $notificationData->setApp($this);
        }

        return $this;
    }

    public function removeNotificationData(NotificationData $notificationData): self
    {
        if ($this->notificationData->contains($notificationData)) {
            $this->notificationData->removeElement($notificationData);
            // set the owning side to null (unless already changed)
            if ($notificationData->getApp() === $this) {
                $notificationData->setApp(null);
            }
        }

        return $this;
    }
}
