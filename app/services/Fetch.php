<?php

namespace App\Services;

class Fetch
{
    /**
     * Makes an HTTP request using cURL.
     *
     * @param string $method HTTP method (GET, POST, DELETE, etc.)
     * @param string $url URL to request
     * @param array $options Request options, including headers and body
     * @return array Response containing 'status', 'headers', and 'body'
     */
    private static function request(string $method, string $url, array $options = []): array
    {
        $curl = curl_init();

        // Set the cURL options
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => self::formatHeaders($headers),
            CURLOPT_HEADER => true,
        ]);

        if (!is_null($body)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        // Execute the request
        $response = curl_exec($curl);

        if ($response === false) {
            throw new \Exception('cURL error: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($curl);

        return [
            'status' => $httpCode,
            'headers' => self::parseHeaders($rawHeaders),
            'body' => $body
        ];
    }

    /**
     * Formats headers array into strings for cURL.
     *
     * @param array $headers Associative array of headers
     * @return array Formatted headers
     */
    private static function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }
        return $formatted;
    }

    /**
     * Parses raw headers into an associative array.
     *
     * @param string $rawHeaders Raw header string
     * @return array Parsed headers
     */
    private static function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($rawHeaders));
        foreach ($lines as $line) {
            if (strpos($line, ': ') !== false) {
                [$key, $value] = explode(': ', $line, 2);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * GET request
     *
     * @param string $url URL to request
     * @param array $options Request options
     * @return array Response
     */
    public static function get(string $url, array $options = []): array
    {
        return self::request('GET', $url, $options);
    }

    /**
     * POST request
     *
     * @param string $url URL to request
     * @param array $options Request options
     * @return array Response
     */
    public static function post(string $url, array $options = []): array
    {
        return self::request('POST', $url, $options);
    }

    /**
     * PUT request
     *
     * @param string $url URL to request
     * @param array $options Request options
     * @return array Response
     */
    public static function put(string $url, array $options = []): array
    {
        return self::request('PUT', $url, $options);
    }

    /**
     * PATCH request
     *
     * @param string $url URL to request
     * @param array $options Request options
     * @return array Response
     */
    public static function patch(string $url, array $options = []): array
    {
        return self::request('PATCH', $url, $options);
    }

    /**
     * DELETE request
     *
     * @param string $url URL to request
     * @param array $options Request options
     * @return array Response
     */
    public static function delete(string $url, array $options = []): array
    {
        return self::request('DELETE', $url, $options);
    }

    /**
     * HEAD request
     *
     * @param string $url URL to request
     * @param array $options Request options
     * @return array Response
     */
    public static function head(string $url, array $options = []): array
    {
        return self::request('HEAD', $url, $options);
    }

    /**
     * OPTIONS request
     *
     * @param string $url URL to request
     * @param array $options Request options
     * @return array Response
     */
    public static function options(string $url, array $options = []): array
    {
        return self::request('OPTIONS', $url, $options);
    }
}