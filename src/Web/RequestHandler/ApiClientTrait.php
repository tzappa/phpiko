<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use Psr\Http\Message\ServerRequestInterface;
use Exception;

/**
 * Trait for making API requests from Web request handlers
 */
trait ApiClientTrait
{
    /**
     * Call an API endpoint
     *
     * @param ServerRequestInterface $request The current request (for host/port info)
     * @param string $endpoint The API endpoint (e.g., '/api/v1/signup')
     * @param array $data The data to send as JSON
     * @param string $method The HTTP method (default: POST)
     * @return array The API response data with 'success' and 'http_code' keys
     * @throws Exception If the API request fails
     */
    private function callApi(
        ServerRequestInterface $request,
        string $endpoint,
        array $data,
        string $method = 'POST'
    ): array {
        // Build the full API URL
        $apiUrl = $this->buildApiUrl($request, $endpoint);

        $ch = curl_init();
        $curlOptions = [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ];

        if (strtoupper($method) === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif (strtoupper($method) === 'PUT') {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("API request failed: $error");
        }

        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }

        return array_merge($responseData, [
            'http_code' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300
        ]);
    }

    /**
     * Build the full API URL based on the current request
     *
     * @param ServerRequestInterface $request
     * @param string $endpoint
     * @return string
     */
    private function buildApiUrl(ServerRequestInterface $request, string $endpoint): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();

        $apiUrl = "{$scheme}://{$host}";

        if ($port && $port !== 80 && $port !== 443) {
            $apiUrl .= ":{$port}";
        }

        $apiUrl .= $endpoint;

        return $apiUrl;
    }
}
