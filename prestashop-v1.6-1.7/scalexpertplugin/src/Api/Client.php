<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace ScalexpertPlugin\Api;

use ScalexpertPlugin\Helper\Hash;
use ScalexpertPlugin\Log\Logger;

/**
 * Construct the client used to make call
 */
class Client
{
    protected $appEnvironnement;
    protected $appIdentifier;
    protected $appKey;
    protected $appBearer;

    public $logger;

    protected static $_instance = null;

    /**
     * @var string
     */
    protected $token;

    public function __construct($appEnv = null, $appIdentifier = null, $appKey = null)
    {
        $this->appEnvironnement = (!is_null($appEnv) ? $appEnv : $this->_getEnvironnement());
        $this->appIdentifier = (!is_null($appIdentifier) ? $appIdentifier : $this->_getApiIdentifier());
        $this->appKey = (!is_null($appKey) ? $appKey : $this->_getApiKey());

        $this->logger = new Logger();
    }

    public function getBearer($scope = null)
    {
        if (!empty($this->appBearer)) {
            return [
                'hasError' => false,
            ];
        }

        $apiUrl = $this->_getApiUrl() . 'auth-server/api/v1/oauth2/token';
        $postFields['grant_type'] = 'client_credentials';
        if ($scope) {
            $postFields['scope'] = $scope . ':rw';
        } else {
            $postFields['scope'] = 'e-financing:rw insurance:rw';
        }

        $uniqId = uniqid('', true);
        $this->logger->logInfo("[".$uniqId."] API CALL : ". json_encode([
                'api_url' => $apiUrl,
                'type' => 'POST',
                'params' => $postFields
            ])
        );

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
            $errorMessage = 'No token';
        }

        if (null !== $errorMessage) {
            $this->logger->logError("[".$uniqId."] API RESPONSE ERROR: ". json_encode([
                    'http_code' => $httpcode,
                    'error_message' => $errorMessage,
                    'response_data' => $data
                ])
            );

            return [
                'hasError' => true,
                'error' => $errorMessage
            ];
        }

        $this->appBearer = $data['access_token'];
        curl_close($ch);

        $this->logger->logInfo("[".$uniqId."] API RESPONSE : ". json_encode([
                'http_code' => $httpcode,
                'response_data' => []
            ])
        );

        return [
            'hasError' => false,
        ];
    }

    private function _getApiUrl()
    {
        return \Configuration::get('SCALEXPERT_API_' . strtoupper($this->appEnvironnement) . '_URL') . '/';
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

    public static function getInstance()
    {
        if (null !== static::$_instance) {
            return static::$_instance;
        }

        return static::$_instance = new Client();
    }

    public static function get($apiUrl)
    {
        $client = static::getInstance();
        $client->getBearer();

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
        $uniqId = uniqid('', true);
        $client->logger->logInfo('['.$uniqId.'][REQUEST] ' . $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $errorMessage = null;
        if (200 !== $httpcode) {
            $errorMessage = 'Error ' . $httpcode;
        }

        $data = json_decode($content, true);

        if (null !== $errorMessage) {
            $client->logger->logError('['.$uniqId.']API RESPONSE ERROR: '. json_encode([
                    'http_code' => $httpcode,
                    'error_message' => $errorMessage,
                    'response_data' => $data
                ])
            );

            return [
                'hasError' => true,
                'error' => $errorMessage
            ];
        }

        $client->logger->logInfo("[".$uniqId."]API RESPONSE : ". json_encode([
                'http_code' => $httpcode,
                'response_data' => $data
            ])
        );

        return [
            'hasError' => false,
            'data' => $data
        ];
    }

    public static function post($apiUrl, $data = [])
    {
        $client = static::getInstance();
        $client->getBearer();

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
        $uniqId = uniqid('', true);
        $client->logger->logInfo('['.$uniqId.'][REQUEST] ' . $url);

        $jsonData = json_encode($data);
        $client->logger->logInfo('['.$uniqId.'][PAYLOAD] ' . $jsonData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $errorMessage = null;
        if (!in_array($httpcode, [
            200,
            201,
            204
        ], true)) {
            $errorMessage = json_decode($content, true);
            $errorMessage = $errorMessage['errorMessage'] ?? $errorMessage;
        }

        $data = json_decode($content, true);

        if (null !== $errorMessage) {
            $client->logger->logError("[".$uniqId."]API RESPONSE ERROR: ". json_encode([
                    'http_code' => $httpcode,
                    'error_message' => $errorMessage,
                    'response_data' => $data
                ])
            );

            return [
                'hasError' => true,
                'error' => $errorMessage
            ];
        }

        $client->logger->logInfo("[".$uniqId."]API RESPONSE : ". json_encode([
                'http_code' => $httpcode,
                'response_data' => $data
            ])
        );

        return [
            'hasError' => false,
            'data' => $data
        ];
    }
}
