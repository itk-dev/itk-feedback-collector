<?php

namespace App\Service;

/**
 * Validates and sanitizes incoming feedback API payloads.
 */
class PayloadValidator
{
    private const MAX_PAYLOAD_SIZE = 5 * 1024 * 1024; // 5 MB
    private const MAX_DATA_SIZE = 2 * 1024 * 1024; // 2 MB

    /**
     * Check whether the request body exceeds the maximum allowed size.
     *
     * @param int $contentLength the Content-Length header value in bytes
     *
     * @return bool true if the payload exceeds 5 MB
     */
    public function isPayloadTooLarge(int $contentLength): bool
    {
        return $contentLength > self::MAX_PAYLOAD_SIZE;
    }

    /**
     * Decode a JSON request body into an associative array.
     *
     * @param string $body the raw request body
     *
     * @return array|null the decoded array, or null if the JSON is invalid
     */
    public function parseJson(string $body): ?array
    {
        $content = json_decode($body, true);

        return is_array($content) ? $content : null;
    }

    /**
     * Check whether the parsed payload contains a valid API key.
     *
     * @param array $content the decoded request payload
     *
     * @return bool true if the apiKey field is a non-empty string
     */
    public function hasValidApiKey(array $content): bool
    {
        return !empty($content['apiKey']) && is_string($content['apiKey']);
    }

    /**
     * Validate the data field of the payload.
     *
     * Checks that data is an array/object and does not exceed 2 MB when
     * JSON-encoded.
     *
     * @param array $content the decoded request payload
     *
     * @return string|null an error message if validation fails, null if valid
     */
    public function validateData(array $content): ?string
    {
        $data = $content['data'] ?? [];

        if (!is_array($data)) {
            return 'Data must be an object or array.';
        }

        $encodedData = json_encode($data);
        if (false === $encodedData || strlen($encodedData) > self::MAX_DATA_SIZE) {
            return 'Data payload too large.';
        }

        return null;
    }

    /**
     * Strip HTML tags from all string values in the data array.
     *
     * Recursively walks the array and applies strip_tags() to every
     * string value to prevent stored HTML/script injection.
     *
     * @param array $data the feedback data to sanitize
     *
     * @return array the sanitized data
     */
    public function sanitizeData(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
            }
        });

        return $data;
    }
}
