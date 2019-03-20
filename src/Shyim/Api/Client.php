<?php

namespace Shyim\Api;

use Shyim\ComposerPlugin;

class Client
{
    const BASE_URL = 'https://api.shopware.com';

    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var array
     */
    private $shop;

    /**
     * @var array
     */
    private $licenses;

    public function __construct(string $username, string $password, string $domain)
    {
        $response = $this->apiRequest('/accesstokens', 'POST', [
            'shopwareId' => $username,
            'password' => $password,
        ]);

        if (isset($response['success']) && $response['success'] === false) {
            throw new \Exception(sprintf('[Installer] Login to Account failed with code %s', $response['code']));
        }

        ComposerPlugin::$io->write('[Installer] Successfully loggedin in the account', true);

        $this->token = $response['token'];
        $this->userId = $response['userId'];

        $this->loadLicenses($domain);
    }

    public function getPartner()
    {
        return $this->apiRequest('/partners/' . $this->userId, 'GET');
    }

    public function getLicenses()
    {
        return $this->licenses;
    }

    public function downloadPlugin(array $binaryVersion)
    {
        return $this->makePluginHTTPRequest(self::BASE_URL . $binaryVersion['filePath'] . '?token=' . $this->token . '&shopId=' . $this->shop['id']);
    }

    /**
     * Download the plugin zip file.
     *
     * if there is no valid license the return is an json like:
     *
     * {"success":false,"code":"PluginsException-1"}
     *
     * @param string $url
     *
     * @return string binary of zipfile or json
     */
    public static function makePluginHTTPRequest($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $content = curl_exec($ch);

        $info = curl_getinfo($ch);

        if (isset($info['content_type']) && $info['content_type'] == 'application/json') {
            $content = json_decode($content, true);
        }

        curl_close($ch);

        return $content;
    }

    private function loadLicenses(string $domain)
    {
        $partnerAccount = $this->apiRequest('/partners/' . $this->userId, 'GET');

        if ($partnerAccount && !empty($partnerAccount['partnerId'])) {
            ComposerPlugin::$io->write('[Installer] Account is partner account', true);

            $clientShops = self::apiRequest('/partners/' . $this->userId . '/clientshops', 'GET');
        } else {
            $clientShops = [];
        }

        $shops = $this->apiRequest('/shops', 'GET', [
            'userId' => $this->userId,
        ]);

        $shops = array_merge($shops, $clientShops, self::getWildcardDomains($this->userId));

        $this->shop = array_filter($shops, function ($shop) use ($domain) {
            return $shop['domain'] === $domain || ($shop['domain'][0] === '.' && strpos($shop['domain'], $domain) !== false);
        });

        if (count($this->shop) === 0) {
            throw new \RuntimeException(sprintf('[Installer] Shop with given domain "%s" does not exist!', $domain));
        }

        $this->shop = array_values($this->shop)[0];

        ComposerPlugin::$io->write(sprintf('[Installer] Found shop with domain "%s" in account', $this->shop['domain']), true);

        if (isset($this->shop['isWildcardShop'])) {
            throw new \RuntimeException((sprintf('[Installer] Domain "%s" is wildcard. Wildcard domains are not supported', $this->shop['domain'])));
        }

        $licenseParams = [
            'shopId' => $this->shop['id'],
        ];

        if ($partnerAccount) {
            $licenseParams['partnerId'] = $this->userId;
        }

        $this->licenses = self::apiRequest('/licenses', 'GET', $licenseParams);

        if (isset($this->licenses['success']) && !$this->licenses['success']) {
            throw new \RuntimeException(sprintf('[Installer] Fetching shop licenses failed with code "%s"!', $this->licenses['code']));
        }
    }

    /**
     * @param string $path
     * @param string $method
     * @param array  $params
     *
     * @return array
     */
    private function apiRequest($path, $method, array $params = [])
    {
        if ($method === 'GET') {
            $path .= '?' . http_build_query($params);
        }

        $ch = curl_init(self::BASE_URL . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        if ($this->token) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-Shopware-Token: ' . $this->token,
                'Useragent: Composer (Shopware-Store-Installer)',
            ]);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get wildcard domains
     *
     * @param int $userId
     *
     * @return array
     */
    private function getWildcardDomains(int $userId)
    {
        $response = $this->apiRequest(sprintf('/wildcardlicenses?companyId=%d', $userId), 'GET');

        if (!isset($response[0]['domain'])) {
            return [];
        }

        return array_map(function ($instance) use ($response) {
            return [
                'id' => $response[0]['id'],
                'instanceId' => $instance['id'],
                'domain' => $instance['name'] . '.' . $response[0]['domain'],
                'isWildcardShop' => true,
            ];
        }, $response[0]['instances']);
    }
}
