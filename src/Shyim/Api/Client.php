<?php

namespace Shyim\Api;

use Shyim\ComposerPlugin;
use Shyim\Struct\License\Binaries;
use Shyim\Struct\License\License;
use Shyim\Struct\Plugin\Plugin;
use Shyim\Struct\Shop\Shop;

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
     * @var Shop
     */
    private $shop;

    /**
     * @var License[]
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

    /**
     * @return License[]
     */
    public function getLicenses(): array
    {
        return $this->licenses;
    }

    public function downloadPlugin(Binaries $binaryVersion)
    {
        return $this->makePluginHTTPRequest(self::BASE_URL . $binaryVersion->filePath . '?token=' . $this->token . '&shopId=' . $this->shop->id);
    }

    /**
     * @return Plugin[]
     */
    public function searchPlugin(string $name)
    {
        $storeRequest = $this->apiRequest('/pluginStore/plugins', 'GET', [
            'locale' => 'en_GB',
            'shopwareVersion' => '__VERSION__',
            'filter' => json_encode([
                [
                    'property' => 'search',
                    'value' => $name,
                    'operator' => null,
                    'expression' => null
                ],
                [
                    'property' => 'price',
                    'value' => 'all',
                    'operator' => null,
                    'expression' => null
                ]
            ]),
            'limit' => 5
        ], false);

        return Plugin::mapList($storeRequest->data);
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

            $clientShops = Shop::mapList(self::apiRequest('/partners/' . $this->userId . '/clientshops', 'GET', [], false));
        } else {
            $clientShops = [];
        }

        $shops = Shop::mapList($this->apiRequest('/shops', 'GET', [
            'userId' => $this->userId,
        ], false));

        $shops = array_merge($shops, $clientShops);

        $this->shop = array_filter($shops, function (Shop $shop) use ($domain) {
            return $shop->domain === $domain || ($shop->domain[0] === '.' && strpos($shop->domain, $domain) !== false);
        });

        if (count($this->shop) === 0) {
            throw new \RuntimeException(sprintf('[Installer] Shop with given domain "%s" does not exist!', $domain));
        }

        $this->shop = array_values($this->shop)[0];

        ComposerPlugin::$io->write(sprintf('[Installer] Found shop with domain "%s" in account', $this->shop->domain), true);

        $licenseParams = [
            'shopId' => $this->shop->id,
        ];

        if ($partnerAccount) {
            $licenseParams['partnerId'] = $this->userId;
        }

        $licenses = self::apiRequest('/licenses', 'GET', $licenseParams, false);

        if (isset($licenses->success) && !$licenses->success) {
            throw new \RuntimeException(sprintf('[Installer] Fetching shop licenses failed with code "%s"!', $licenses->code));
        }

        $this->licenses = License::mapList($licenses);
    }

    /**
     * @return array
     */
    private function apiRequest(string $path, string $method, array $params = [], bool $assoc = true)
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

        return json_decode($response, $assoc);
    }
}
