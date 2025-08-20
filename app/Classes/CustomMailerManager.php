<?php

namespace App\Classes;

class CustomMailerManager
{
    private string $name;

    private string $email;

    private string $themeColor;

    private string $logo;

    private string $logoHref;

    private string $unsubscriptionUrl;

    private string $privacyPolicyUrl;

    private string $helpCenterUrl;

    public function __construct(string $name,
        string $themeColor,
        string $email,
        string $logo,
        string $logoHref = '#',
        string $unsubscriptionUrl = '#',
        string $privacyPolicyUrl = '#',
        string $helpCenterUrl = '#')
    {
        $this->name = $name;
        $this->themeColor = $themeColor;
        $this->email = $email;
        $this->logo = $logo;
        $this->logoHref = $logoHref;
        $this->unsubscriptionUrl = $unsubscriptionUrl;
        $this->privacyPolicyUrl = $privacyPolicyUrl;
        $this->helpCenterUrl = $helpCenterUrl;
    }

    /*
     * Getters
     */

    public function getName(): string
    {
        return $this->name;
    }

    public function getThemeColor(): string
    {
        return $this->themeColor;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function getLogoHref(): string
    {
        return $this->logoHref;
    }

    public function getUnsubscriptionUrl(): string
    {
        return $this->unsubscriptionUrl;
    }

    public function getPrivacyPolicyUrl(): string
    {
        return $this->privacyPolicyUrl;
    }

    public function getHelpCenterUrl(): string
    {
        return $this->helpCenterUrl;
    }

    /*
     * Setters
     */

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setThemeColor(string $themeColor): void
    {
        $this->themeColor = $themeColor;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
    }

    public function setLogoHref(string $logoHref): void
    {
        $this->logoHref = $logoHref;
    }

    public function setUnsubscriptionUrl(string $unsubscriptionUrl): void
    {
        $this->unsubscriptionUrl = $unsubscriptionUrl;
    }

    public function setPrivacyPolicyUrl(string $privacyPolicyUrl): void
    {
        $this->privacyPolicyUrl = $privacyPolicyUrl;
    }

    public function setHelpCenterUrl(string $helpCenterUrl): void
    {
        $this->helpCenterUrl = $helpCenterUrl;
    }

    /*
     * Methods
     */

    /**
     * Return all the values as an array
     */
    public function setData(array $data = []): array
    {
        return [
            ...$data,
            'name' => $this->name,
            'logo' => $this->logo,
            'logoHref' => $this->logoHref,
            'themeColor' => $this->themeColor,
            'unsubscription' => $this->unsubscriptionUrl,
            'privacyPolicy' => $this->privacyPolicyUrl,
            'helpCenter' => $this->helpCenterUrl,
        ];
    }
}
