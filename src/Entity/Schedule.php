<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ScheduleRepository::class)
 */
class Schedule
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
    private $delivery;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $optimisation;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $time;

    /**
     * @ORM\OneToOne(targetEntity=Notification::class, inversedBy="schedule", cascade={"persist", "remove"})
     */
    private $notification;

    /**
     * @ORM\OneToMany(targetEntity=Weekdays::class, mappedBy="schedule")
     */
    private $weekdays;

    public function __construct()
    {
        $this->weekdays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDelivery(): ?string
    {
        return $this->delivery;
    }

    public function setDelivery(string $delivery): self
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getOptimisation(): ?string
    {
        return $this->optimisation;
    }

    public function setOptimisation(string $optimisation): self
    {
        $this->optimisation = $optimisation;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(?\DateTimeInterface $time): self
    {
        $this->time = $time;

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

    /**
     * @return Collection|Weekdays[]
     */
    public function getWeekdays(): Collection
    {
        return $this->weekdays;
    }

    public function addWeekday(Weekdays $weekday): self
    {
        if (!$this->weekdays->contains($weekday)) {
            $this->weekdays[] = $weekday;
            $weekday->setSchedule($this);
        }

        return $this;
    }

    public function removeWeekday(Weekdays $weekday): self
    {
        if ($this->weekdays->removeElement($weekday)) {
            // set the owning side to null (unless already changed)
            if ($weekday->getSchedule() === $this) {
                $weekday->setSchedule(null);
            }
        }

        return $this;
    }
}
