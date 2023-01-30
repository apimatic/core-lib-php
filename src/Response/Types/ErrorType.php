<?php

declare(strict_types=1);

namespace Core\Response\Types;

use Core\Response\Context;
use Core\Utils\CoreHelper;
use Rs\Json\Pointer;

class ErrorType
{
    /**
     * Initializes a new object with the description and class name provided.
     */
    public static function init(string $description, ?string $className = null, bool $hasErrorTemplate = false): self
    {
        return new self($description, $className, $hasErrorTemplate);
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

    private function updateHeaderPlaceHolderValues($errorDescription, $response)
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

    private function updateResponsePlaceholderValues($errorDescription, $jsonPointersInTemplate, $response)
    {
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

    private function getJsonPointersFromTemplate($template)
    {
        $pointerPattern = '/#[\w\/]*/i';

        preg_match_all($pointerPattern, $template, $matches);

        return $matches;
    }

    private function addPlaceHolderValue($template, $placeHolder, $value)
    {
        if (!is_string($value)) {
            $value = var_export($value, true);
        }

        return str_replace($placeHolder, $value, $template);
    }

    private function getHeaderKeys($headers)
    {
        return array_keys($headers);
    }

    private function getJsonPointerValue($jsonPointer, $pointer)
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

    private function initializeJsonPointer($response)
    {
        $rawBody = $response->getRawBody();
        $jsonResponsePointer = null;

        if ($this->isJson($rawBody)) {
            $jsonResponsePointer = new Pointer($rawBody);
        }

        return $jsonResponsePointer;
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
