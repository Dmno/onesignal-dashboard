<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 */
class Notification
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
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class, inversedBy="notifications")
     * @ORM\JoinColumn(nullable=false)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity=Image::class, inversedBy="notifications")
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity=Icon::class, inversedBy="notifications")
     */
    private $icon;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @ORM\Column(type="boolean")
     */
    private $saved = false;

    /**
     * @ORM\Column(type="integer")
     */
    private $sends = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastSent;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $original;

    /**
     * @ORM\OneToOne(targetEntity=Schedule::class, mappedBy="notification", cascade={"persist", "remove"})
     */
    private $schedule;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notifications")
     */
    private $User;

    /**
     * @ORM\OneToMany(targetEntity=NotificationData::class, mappedBy="notification", cascade={"persist", "remove"})
     */
    private $notificationData;

    /**
     * @ORM\ManyToMany(targetEntity=Campaign::class, inversedBy="notifications")
     */
    private $campaign;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timesSent;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $paused = false;

    /**
     * @ORM\OneToOne(targetEntity=NotificationStats::class, mappedBy="notification", cascade={"persist", "remove"})
     */
    private $notificationStats;


    public function __construct()
    {
        $this->notificationData = new ArrayCollection();
        $this->campaign = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getSchedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function setSchedule(?Schedule $schedule): self
    {
        $this->schedule = $schedule;

        // set (or unset) the owning side of the relation if necessary
        $newNotification = null === $schedule ? null : $this;
        if ($schedule->getNotification() !== $newNotification) {
            $schedule->setNotification($newNotification);
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): self
    {
        $this->User = $User;

        return $this;
    }

    public function getSaved(): ?bool
    {
        return $this->saved;
    }

    public function setSaved(bool $saved): self
    {
        $this->saved = $saved;

        return $this;
    }

    public function getSends(): ?int
    {
        return $this->sends;
    }

    public function setSends(int $sends): self
    {
        $this->sends = $sends;

        return $this;
    }

    public function getLastSent(): ?\DateTimeInterface
    {
        return $this->lastSent;
    }

    public function setLastSent(\DateTimeInterface $lastSent): self
    {
        $this->lastSent = $lastSent;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getOriginal(): ?string
    {
        return $this->original;
    }

    public function setOriginal(?string $original): self
    {
        $this->original = $original;

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
            $notificationData->setNotification($this);
        }

        return $this;
    }

    public function removeNotificationData(NotificationData $notificationData): self
    {
        if ($this->notificationData->contains($notificationData)) {
            $this->notificationData->removeElement($notificationData);
            // set the owning side to null (unless already changed)
            if ($notificationData->getNotification() === $this) {
                $notificationData->setNotification(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Campaign[]
     */
    public function getCampaign(): Collection
    {
        return $this->campaign;
    }

    public function addCampaign(Campaign $campaign): self
    {
        if (!$this->campaign->contains($campaign)) {
            $this->campaign[] = $campaign;
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): self
    {
        if ($this->campaign->contains($campaign)) {
            $this->campaign->removeElement($campaign);
        }

        return $this;
    }

    public function getTimesSent(): ?int
    {
        return $this->timesSent;
    }

    public function setTimesSent(?int $timesSent): self
    {
        $this->timesSent = $timesSent;

        return $this;
    }

    public function getPaused(): ?bool
    {
        return $this->paused;
    }

    public function setPaused(?bool $paused): self
    {
        $this->paused = $paused;

        return $this;
    }

    public function getNotificationStats(): ?NotificationStats
    {
        return $this->notificationStats;
    }

    public function setNotificationStats(?NotificationStats $notificationStats): self
    {
        $this->notificationStats = $notificationStats;

        // set (or unset) the owning side of the relation if necessary
        $newNotification = null === $notificationStats ? null : $this;
        if ($notificationStats->getNotification() !== $newNotification) {
            $notificationStats->setNotification($newNotification);
        }

        return $this;
    }
}
