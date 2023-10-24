<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

namespace DATASOLUTION\Module\Scalexpert\Api;

use DATASOLUTION\Module\Scalexpert\Helper\Hash;
use DATASOLUTION\Module\Scalexpert\Log\Logger;

/**
 * Construct the client used to make call
 */
class Client
{
    protected $appEnvironnement;
    protected $appIdentifier;
    protected $appKey;
    protected $appBearer;

    /**
     * @var string
     */
    protected $token;

    public function __construct($appEnv = null, $appIdentifier = null, $appKey = null)
    {
        $this->appEnvironnement = (!is_null($appEnv) ? $appEnv : $this->_getEnvironnement());
        $this->appIdentifier = (!is_null($appIdentifier) ? $appIdentifier : $this->_getApiIdentifier());
        $this->appKey = (!is_null($appKey) ? $appKey : $this->_getApiKey());
    }

    public function getBearer($scope = null)
    {
        $apiUrl = $this->_getApiUrl() . 'auth-server/api/v1/oauth2/token';
        $postFields['grant_type'] = 'client_credentials';
        if ($scope) {
            $postFields['scope'] = $scope . ':rw';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->appIdentifier . ':' . $this->appKey)
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $data = json_decode($content, true);

        $errorMessage = null;
        if (200 !== $httpcode) {
            $errorMessage = 'Error ' . $httpcode;
        } elseif (empty($data['access_token'])) {
            $errorMessage = 'No token'; //
        }

        if (null !== $errorMessage) {
            return [
                'hasError' => true,
                'error' => $errorMessage
            ];
        }

        $this->appBearer = $data['access_token'];
        curl_close($ch);

        return [
            'hasError' => false,
        ];
    }

    private function _getApiUrl()
    {
        if (strtoupper($this->appEnvironnement) == 'PRODUCTION') {
            $apiURL = 'https://api.scalexpert.societegenerale.com/baas/prod/';
        } else {
            $apiURL = 'https://api.scalexpert.uatc.societegenerale.com/baas/uatc';
        }

        return $apiURL;
    }

    private function _getApiIdentifier()
    {
        return \Configuration::get('SCALEXPERT_API_' . strtoupper($this->appEnvironnement) . '_IDENTIFIER');
    }

    private function _getApiKey()
    {
        return Hash::decrypt(\Configuration::get('SCALEXPERT_API_' . strtoupper($this->appEnvironnement) . '_KEY'));
    }

    private function _getEnvironnement()
    {
        return \Configuration::get('SCALEXPERT_ENVIRONMENT');
    }

    public static function get($scope, $apiUrl)
    {
        $logger = new Logger();

        $client = new Client();
        $client->getBearer($scope);

        if (!$client->appBearer) {
            return [
                'hasError' => true,
                'error' => 'Authentication failed- No bearer'
            ];
        }

        $url = $client->_getApiUrl() . $apiUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $client->appBearer,
            'Cache-control: no-cache',
        ]);
        $logger->logInfo('[REQUEST] ' . $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $logger->logInfo('[RESPONSE] ' . $content);

        $errorMessage = null;
        if (200 !== $httpcode) {
            $errorMessage = 'Error ' . $httpcode;
        }
        if (null !== $errorMessage) {
            return [
                'hasError' => true,
                'error' => $errorMessage
            ];
        }

        $data = json_decode($content, true);
        return [
            'hasError' => false,
            'data' => $data
        ];
    }

    public static function post($scope, $apiUrl, $data = [])
    {
        $logger = new Logger();

        $client = new Client();
        $client->getBearer($scope);
        if (!$client->appBearer) {
            return [
                'hasError' => true,
                'error' => 'Authentication failed- No bearer'
            ];
        }

        $url = $client->_getApiUrl() . $apiUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $client->appBearer,
            'Accept: application/json',
            'Content-Type: application/json',
            'Cache-control: no-cache',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        $logger->logInfo('[REQUEST] ' . $url);

        $jsonData = json_encode($data);
        $logger->logInfo('[PAYLOAD] ' . $jsonData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $logger->logInfo('[RESPONSE] ' . $content);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $errorMessage = null;
        if (200 !== $httpcode && 201 !== $httpcode) {
            $errorMessage = json_decode($content, true);
            $errorMessage = $errorMessage['errorMessage'] ?? $errorMessage;
        }
        if (null !== $errorMessage) {
            return [
                'hasError' => true,
                'error' => $errorMessage
            ];
        }

        $data = json_decode($content, true);
        return [
            'hasError' => false,
            'data' => $data
        ];
    }
}
