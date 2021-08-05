<?php


namespace App\Form\Model;


class AppRecordModel
{
    private $appId;
    private $name;
    private $domain;
    private $totalUsers;
    private $subscribedUsers;
    private $authKey;

    public function __construct(string $appId, string $name, string $domain, int $totalUsers, int $subscribedUsers, string $authKey)
    {
        $this->appId = $appId;
        $this->name = $name;
        $this->domain = $domain;
        $this->totalUsers = $totalUsers;
        $this->subscribedUsers = $subscribedUsers;
        $this->authKey = $authKey;
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
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
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return int
     */
    public function getTotalUsers(): int
    {
        return $this->totalUsers;
    }

    /**
     * @param int $totalUsers
     */
    public function setTotalUsers(int $totalUsers): void
    {
        $this->totalUsers = $totalUsers;
    }

    /**
     * @return int
     */
    public function getSubscribedUsers(): int
    {
        return $this->subscribedUsers;
    }

    /**
     * @param int $subscribedUsers
     */
    public function setSubscribedUsers(int $subscribedUsers): void
    {
        $this->subscribedUsers = $subscribedUsers;
    }

    /**
     * @return string
     */
    public function getAuthKey(): string
    {
        return $this->authKey;
    }

    /**
     * @param string $authKey
     */
    public function setAuthKey(string $authKey): void
    {
        $this->authKey = $authKey;
    }
}