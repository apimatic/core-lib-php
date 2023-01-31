<?php

declare(strict_types=1);

namespace Core\Response\Types;

use Core\Response\Context;
use Core\Utils\CoreHelper;
use CoreInterfaces\Core\Response\ResponseInterface;
use Rs\Json\Pointer;

class ErrorType
{
    /**
     * Initializes a new object with the description and class name provided.
     */
    public static function init(string $description, ?string $className = null): self
    {
        return new self($description, $className, false);
    }

    /**
     * Initializes a new object with error template and class name provided.
     */
    public static function initWithErrorTemplate(string $errorTemplate, ?string $className = null): self
    {
        return new self($errorTemplate, $className, true);
    }

    private $description;
    private $className;
    private $hasErrorTemplate;

    private function __construct(string $description, ?string $className, bool $hasErrorTemplate)
    {
        $this->description = $description;
        $this->className = $className;
        $this->hasErrorTemplate = $hasErrorTemplate;
    }

    /**
     * Throws an Api exception from the context provided.
     *
     * @param Context $context
     */
    public function throwable(Context $context)
    {
        if ($this->hasErrorTemplate) {
            $response = $context->getResponse();

            $errorDescriptionTemplate = $this->description;

            $jsonPointersInTemplate = $this->getJsonPointersFromTemplate($errorDescriptionTemplate);

            $errorDescription = $this->updateResponsePlaceholderValues(
                $errorDescriptionTemplate,
                $jsonPointersInTemplate,
                $response
            );

            $errorDescription = $this->updateHeaderPlaceHolderValues($errorDescription, $response);

            $errorDescription = $this->addPlaceHolderValue(
                $errorDescription,
                '{$statusCode}',
                $response->getStatusCode()
            );

            $this->description = $errorDescription;
        }

        return $context->toApiException($this->description, $this->className);
    }

    private function updateHeaderPlaceHolderValues(string $errorDescription, ResponseInterface $response): string
    {
        $headers = $response->getHeaders();
        $headerKeys = $this->getHeaderKeys($headers);

        for ($x = 0; $x < count($headerKeys); $x++) {
            $errorDescription = $this->addPlaceHolderValue(
                $errorDescription,
                '{$response.header.' . $headerKeys[$x] . '}',
                $headers[$headerKeys[$x]]
            );
        }

        return $errorDescription;
    }

    /**
     * @param $errorDescription string
     * @param $jsonPointersInTemplate string[]
     * @param $response ResponseInterface
     * @return string Updated error string template.
     */
    private function updateResponsePlaceholderValues(
        string $errorDescription,
        array $jsonPointersInTemplate,
        ResponseInterface $response
    ): string {
        if (count($jsonPointersInTemplate[0]) > 0) {
            $jsonResponsePointer = $this->initializeJsonPointer($response);

            $jsonPointers = $jsonPointersInTemplate[0];

            for ($x = 0; $x < count($jsonPointers); $x++) {
                $placeHolderValue = "";

                if ($jsonResponsePointer != null) {
                    $placeHolderValue = $this->getJsonPointerValue($jsonResponsePointer, ltrim($jsonPointers[$x], '#'));
                }

                $errorDescription = $this->addPlaceHolderValue(
                    $errorDescription,
                    '{$response.body' . $jsonPointers[$x] . '}',
                    $placeHolderValue
                );
            }

            return $errorDescription;
        }

        return $this->addPlaceHolderValue(
            $errorDescription,
            '{$response.body}',
            $response->getRawBody()
        );
    }

    private function getJsonPointersFromTemplate(string $template): array
    {
        $pointerPattern = '/#[\w\/]*/i';

        preg_match_all($pointerPattern, $template, $matches);

        return $matches;
    }

    private function addPlaceHolderValue(string $template, string $placeHolder, $value): string
    {
        if (!is_string($value)) {
            $value = var_export($value, true);
        }

        return str_replace($placeHolder, $value, $template);
    }

    /**
     * @param $headers array
     * @return int[]|string[]
     */
    private function getHeaderKeys(array $headers)
    {
        return array_keys($headers);
    }

    /**
     * @param $jsonPointer Pointer
     * @param $pointer string
     * @return mixed Json pointer value from the JSON provided.
     */
    private function getJsonPointerValue(Pointer $jsonPointer, string $pointer)
    {
        try {
            if (trim($pointer) === '') {
                return "";
            }

            $pointerValue = $jsonPointer->get($pointer);

            if (is_object($pointerValue)) {
                return CoreHelper::serialize($pointerValue);
            }

            return $pointerValue;
        } catch (Pointer\NonexistentValueReferencedException $ex) {
            return "";
        }
    }

    private function initializeJsonPointer(ResponseInterface $response): ?Pointer
    {
        $rawBody = $response->getRawBody();
        $jsonResponsePointer = null;

        if ($this->isJson($rawBody)) {
            $jsonResponsePointer = new Pointer($rawBody);
        }

        return $jsonResponsePointer;
    }

    private function isJson(string $string): bool
    {
        $decoded = json_decode($string);
        return is_object($decoded) || json_last_error() === JSON_ERROR_NONE;
    }
}
