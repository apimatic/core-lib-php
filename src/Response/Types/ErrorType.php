<?php

declare(strict_types=1);

namespace Core\Response\Types;

use Core\Response\Context;
use Core\Utils\CoreHelper;
use PHP_CodeSniffer\Reports\Source;
use Rs\Json\Pointer;
use SebastianBergmann\Invoker\ProcessControlExtensionNotLoadedException;

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

            $statusCodePlaceholder = '${statusCode}';
            $errorDescription = $this->addPlaceHolderValue(
                $errorDescription,
                $statusCodePlaceholder,
                $response->getStatusCode()
            );

            $this->description = $errorDescription;
        }

        return $context->toApiException($this->description, $this->className);
    }

    private function updateHeaderPlaceHolderValues($errorDescription, $response)
    {
        $headers = $response->getHeaders();
        $headerKeys = $this->getHeaderKeys($response);

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
        if (count($jsonPointersInTemplate) > 0) {
            // Response can only be in the body?
            $jsonResponsePointer = $this->initializeJsonPointer($response);

            if ($jsonResponsePointer == null) {
                throw new Pointer\InvalidJsonException("Could not create an instance of JsonPointer.");
            }

            $jsonPointers = $jsonPointersInTemplate[0];

            for ($x = 0; $x < count($jsonPointers); $x++) {
                $errorDescription = $this->addPlaceHolderValue($errorDescription,
                    '{$response.body' . $jsonPointers[$x] . '}',
                    $this->getJsonPointerValue($jsonResponsePointer, ltrim($jsonPointers[$x], '#')));
            }

            return $errorDescription;
        }

        if (is_object($response->getBody())) {
            return $this->addPlaceHolderValue($errorDescription,
                '{$response.body}', CoreHelper::serialize($response->getBody()));
        }

        return $this->addPlaceHolderValue($errorDescription,
            '{$response.body}', $response->getRawBody());
    }

    private function getJsonPointersFromTemplate($template)
    {
        $pointerPattern = '/#[\w\/]*/i';

        preg_match_all($pointerPattern, $template, $matches);

        return $matches;
    }

    private function addPlaceHolderValue($template, $placeHolder, $value)
    {
        return str_replace($placeHolder, $value, $template);
    }

    private function getHeaderKeys($response)
    {
        return array_keys($response->getHeaders());
    }

    private function getJsonPointerValue($jsonPointer, $pointer)
    {
        // handle empty string pointer
        // returns the complete json string as string in case of empty
        try {
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
        try {
            return new Pointer($response->getRawBody());
        } catch (Pointer\InvalidJsonException $ex) {
            return null;
        }
    }
}
