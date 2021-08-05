<?php

namespace App\Form\Model;

class NotificationAndScheduleModel
{
    public $name;
    public $country;
    public $title;
    public $message;
    public $icon;
    public $image;
    public $url;
    public $delivery;
    public $date;
    public $optimisation;
    public $store;

    public function __construct(
        string $name,
        object $country,
        string $title,
        string $message,
        string $icon = null,
        string $image = null,
        string $url,
        string $delivery,
        string $date = null,
        string $optimisation,
        bool $store = false
    )
    {
        $this->name = $name;
        $this->country = $country;
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->image = $image;
        $this->url = $url;
        $this->delivery = $delivery;
        $this->date = $date;
        $this->optimisation = $optimisation;
        $this->store = $store;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return object
     */
    public function getCountry(): object
    {
        return $this->country;
    }

    /**
     * @param object $country
     */
    public function setCountry(object $country): void
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getDelivery(): string
    {
        return $this->delivery;
    }

    /**
     * @param string $delivery
     */
    public function setDelivery(string $delivery): void
    {
        $this->delivery = $delivery;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getOptimisation(): string
    {
        return $this->optimisation;
    }

    /**
     * @param string $optimisation
     */
    public function setOptimisation(string $optimisation): void
    {
        $this->optimisation = $optimisation;
    }

    /**
     * @return bool
     */
    public function isStore(): bool
    {
        return $this->store;
    }

    /**
     * @param bool $store
     */
    public function setStore(bool $store): void
    {
        $this->store = $store;
    }
}