<?php

namespace App\Entity;

use App\Repository\NotificationDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationDataRepository::class)
 */
class NotificationData
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
    private $sentNotificationId;

    /**
     * @ORM\ManyToOne(targetEntity=Notification::class, inversedBy="notificationData")
     * @ORM\JoinColumn(nullable=false)
     */
    private $notification;

    /**
     * @ORM\ManyToOne(targetEntity=App::class, inversedBy="notificationData")
     * @ORM\JoinColumn(nullable=false)
     */
    private $app;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $checkCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSentNotificationId(): ?string
    {
        return $this->sentNotificationId;
    }

    public function setSentNotificationId(string $sentNotificationId): self
    {
        $this->sentNotificationId = $sentNotificationId;

        return $this;
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

    public function getApp(): ?App
    {
        return $this->app;
    }

    public function setApp(?App $app): self
    {
        $this->app = $app;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

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
