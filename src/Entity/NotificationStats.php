<?php

namespace App\Entity;

use App\Repository\NotificationStatsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationStatsRepository::class)
 */
class NotificationStats
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Notification::class, inversedBy="notificationStats", cascade={"persist", "remove"})
     */
    private $notification;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalReceivers;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalConversions;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstCheckReceivers;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstCheckConversions;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lastCheckReceivers;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lastCheckConversions;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastCheckDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $checkCount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotification(): ?Notification
    {
        return $this->notification;
    }

    public function setNotification(?Notification $notification): self
    {
        $this->notification = $notification;

        return $this;
    }

    public function getTotalReceivers(): ?int
    {
        return $this->totalReceivers;
    }

    public function setTotalReceivers(?int $totalReceivers): self
    {
        $this->totalReceivers = $totalReceivers;

        return $this;
    }

    public function getTotalConversions(): ?int
    {
        return $this->totalConversions;
    }

    public function setTotalConversions(?int $totalConversions): self
    {
        $this->totalConversions = $totalConversions;

        return $this;
    }

    public function getFirstCheckReceivers(): ?int
    {
        return $this->firstCheckReceivers;
    }

    public function setFirstCheckReceivers(?int $firstCheckReceivers): self
    {
        $this->firstCheckReceivers = $firstCheckReceivers;

        return $this;
    }

    public function getFirstCheckConversions(): ?int
    {
        return $this->firstCheckConversions;
    }

    public function setFirstCheckConversions(?int $firstCheckConversions): self
    {
        $this->firstCheckConversions = $firstCheckConversions;

        return $this;
    }

    public function getLastCheckReceivers(): ?int
    {
        return $this->lastCheckReceivers;
    }

    public function setLastCheckReceivers(?int $lastCheckReceivers): self
    {
        $this->lastCheckReceivers = $lastCheckReceivers;

        return $this;
    }

    public function getLastCheckConversions(): ?int
    {
        return $this->lastCheckConversions;
    }

    public function setLastCheckConversions(?int $lastCheckConversions): self
    {
        $this->lastCheckConversions = $lastCheckConversions;

        return $this;
    }

    public function getLastCheckDate(): ?\DateTimeInterface
    {
        return $this->lastCheckDate;
    }

    public function setLastCheckDate(?\DateTimeInterface $lastCheckDate): self
    {
        $this->lastCheckDate = $lastCheckDate;

        return $this;
    }

    public function getCheckCount(): ?int
    {
        return $this->checkCount;
    }

    public function setCheckCount(?int $checkCount): self
    {
        $this->checkCount = $checkCount;

        return $this;
    }
}
