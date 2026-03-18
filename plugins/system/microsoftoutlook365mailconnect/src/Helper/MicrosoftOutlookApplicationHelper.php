<?php

/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.8 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html   
 # Website: https://www.web357.com
 # Demo: 
 # Support: support@web357.com
 # Last modified: Tuesday 03 February 2026, 10:20:16 AM
 ========================================================= */
declare(strict_types=1);

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Helper;

defined('_JEXEC') or die;

use Datetime;
use Exception;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Extension\Microsoftoutlook365mailconnect;

class MicrosoftOutlookApplicationHelper
{

    /** @var string */
    protected $applicationId;

    /** @var string */
    protected $clientSecret;

    /** @var string */
    protected $accessTokenData;

    /** @var string */
    protected $redirectUrl = '';

    /** @var string */
    protected $scopes = 'Mail.Send Mail.Send.Shared User.Read offline_access';

    /** @var string */
    protected $tenant = 'common';

    /** @var string */
    protected $oauthFromEmail = '';

    /** @var MicrosoftOutlookApplicationHelper */
    protected static $instance;

    public function __construct()
    {
        $pluginParams = Microsoftoutlook365mailconnect::getPluginParams();
        $this->applicationId = $pluginParams->get('oauth_application_id');
        $this->clientSecret = $pluginParams->get('oauth_client_secret');
        $this->tenant = $pluginParams->get('oauth_tenant_id', 'common');
        $this->oauthFromEmail = $pluginParams->get('oauth_from_email', '');
        $this->accessTokenData = $pluginParams->get('oauth_access_token');
        if ($this->accessTokenData) {
            $this->accessTokenData = json_decode($this->accessTokenData, true);
        }
        $this->redirectUrl = Uri::root() . 'index.php/ms365/microsoft-outlook-365-mail-connect-authorize';
    }

    /**
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->applicationId && $this->clientSecret;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return (bool)$this->accessTokenData;
    }

    /**
     * @return string
     */
    public function getOauthFromEmail(): string
    {
        return $this->oauthFromEmail;
    }

    /**
     * @return string
     */
    public function getConfiguredEmail(): string
    {
        return $this->accessTokenData['email_address'] ?? '';
    }

    /**
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        return 'https://login.microsoftonline.com/' . rawurlencode($this->tenant) . '/oauth2/v2.0/authorize?' . http_build_query([
                'client_id' => $this->applicationId,
                'response_type' => 'code',
                'redirect_uri' => $this->redirectUrl,
                'scope' => $this->scopes,
            ]);
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * Fetches access and refresh token after user authorizes the application
     * @param string $code
     * @return bool
     * @throws Exception
     */
    public function authorize(string $code): bool
    {
        $response = $this->makeCurlRequest(
            'https://login.microsoftonline.com/' . rawurlencode($this->tenant) . '/oauth2/v2.0/token',
            http_build_query([
                'client_id' => $this->applicationId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUrl,
                'scope' => $this->scopes,
            ]), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        if ($response['httpCode'] !== 200) {
            throw new Exception($response['error']);
        }
        $this->saveAccessTokenData($response['data']);
        return true;
    }

    /**
     * Saves access token data to database
     */
    protected function saveAccessTokenData(array $responseData): void
    {
        if (empty($responseData['access_token']) || empty($responseData['refresh_token'])) {
            throw new Exception('Empty access token');
        }
        $response = $this->makeCurlRequest('https://graph.microsoft.com/v1.0/me', '', [
            "Authorization: Bearer " . $responseData['access_token'],
        ], 'GET');

        if ($response['httpCode'] !== 200) {
            throw new Exception('Error fetching user data');
        }

        $emailAddress = isset($response['data']['mail']) ? $response['data']['userPrincipalName'] : '';
        $this->accessTokenData = [
            'email_address' => $emailAddress,
            'access_token' => $responseData['access_token'],
            'refresh_token' => $responseData['refresh_token'],
            'expires_in' => ((int)$responseData['expires_in'] + (int)(new DateTime())->format('U')),
        ];
        Microsoftoutlook365mailconnect::savePluginParams([
            'oauth_access_token' => json_encode($this->accessTokenData),
        ]);
    }

    /**
     * Retrieves the active access token, refreshing it if necessary.
     *
     * @return string The active access token.
     * @throws Exception If the token refresh request fails or returns an error.
     */
    protected function getActiveAccessToken(): string
    {
        $now = (int)(new DateTime())->format('U');

        // If token expired, try to refresh
        if (!empty($this->accessTokenData['expires_in']) && $this->accessTokenData['expires_in'] < $now) {
            $httpRequestParams = [
                'client_id' => $this->applicationId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->accessTokenData['refresh_token'] ?? '',
                'grant_type' => 'refresh_token',
                'scope' => $this->scopes,
            ];
            $response = $this->makeCurlRequest(
                'https://login.microsoftonline.com/' . rawurlencode($this->tenant) . '/oauth2/v2.0/token',
                http_build_query($httpRequestParams),
                ['Content-Type: application/x-www-form-urlencoded']
            );
            if ($response['httpCode'] === 200) {
                $this->saveAccessTokenData($response['data']);
            } else {
                foreach (['client_id', 'client_secret', 'refresh_token'] as $param) {
                    $httpRequestParams[$param] = substr((string)$httpRequestParams[$param], 0, 5) . '-ANON';
                }
                Log::add('Failed to refresh token: ' . $response['error'] . ' ' . json_encode($response) . '|' . json_encode($httpRequestParams), Log::ERROR, 'plg_system_microsoftoutlook365mailconnect');
                $this->accessTokenData = [];
                Microsoftoutlook365mailconnect::savePluginParams([
                    'oauth_access_token' => null,
                ]);
                throw new Exception('Token refresh failed. Please reauthorize the app.', $response['httpCode']);
            }
        }

        return $this->accessTokenData['access_token'] ?? '';
    }

    public function sendEmail(array $emailData): bool
    {
        $apiUrl = $this->getConfiguredEmail() ? 'https://graph.microsoft.com/v1.0/users/' . $this->getConfiguredEmail() . '/sendMail' : 'https://graph.microsoft.com/v1.0/me/sendMail';
        $response = $this->makeCurlRequest($apiUrl, json_encode($emailData), [
                "Authorization: Bearer " . $this->getActiveAccessToken(),
                "Content-Type: application/json",
            ]
        );

        if ($response['httpCode'] !== 202) {
            $errorMessage = $response['error'];
            if (isset($response['data'])) {
                $errorData = is_string($response['data']) ? json_decode($response['data'], true) : $response['data'];
                if ($errorData && isset($errorData['error']['message'])) {
                    $errorMessage = $errorData['error']['message'];
                    if (isset($errorData['error']['details'])) {
                        $errorMessage .= ' Details: ' . json_encode($errorData['error']['details']);
                    }
                }
            }
            throw new Exception('Microsoft Graph API Error Response: ' . $errorMessage, $response['httpCode']);
        }
        return true;
    }

    /**
     * Makes a cURL request with the specified parameters
     *
     * @param string $url The URL to make the request to
     * @param string $postData The data to send in the request (for POST/PUT requests)
     * @param array $headers The headers to include in the request
     * @param string $requestType The type of request (GET, POST, PUT, DELETE, etc.)
     * @return array An associative array containing:
     *               - 'data' => The response from the server
     *               - 'http_code' => The HTTP status code
     *               - 'error' => Any error message (if applicable)
     */
    protected function makeCurlRequest(string $url, string $postData = '', array $headers = [], string $requestType = 'POST')
    {
        $ch = curl_init();

        // Prepare the base options
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        // Set request-specific options
        $requestType = strtoupper($requestType);
        if ($requestType === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postData;
        } elseif ($requestType !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $requestType;
            $options[CURLOPT_POSTFIELDS] = $postData;
        }

        // Apply all options at once
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $return = $response ? (array)json_decode($response, true) : [];
        if (!$error) {
            if (isset($return['error_description'])) {
                $error = $return['error_description'];
            } elseif (isset($return['error']['message'])) {
                $error = $return['error']['message'];
            }
        }

        if ($error) {
            Log::add('Error in Microsoft/Outlook 365 Mail Connect request: ' . $error, Log::ERROR, 'plg_system_microsoftoutlook365mailconnect');
        }

        return [
            'data' => $return,
            'httpCode' => $httpCode,
            'error' => $error ?: null,
        ];
    }

    /**
     * Returns the singleton instance of the class.
     * @return self The single instance of the class.
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}