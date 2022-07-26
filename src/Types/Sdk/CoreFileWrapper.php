<?php

declare(strict_types=1);

namespace CoreLib\Types\Sdk;

use CURLFile;
use SplFileObject;

class CoreFileWrapper
{
    /**
     * @var string
     */
    private $realFilePath;

    /**
     * @var string|null
     */
    private $mimeType;

    /**
     * @var string|null
     */
    private $filename;

    public function __construct(string $realFilePath, ?string $mimeType, ?string $filename)
    {
        $this->realFilePath = $realFilePath;
        $this->mimeType = $mimeType;
        $this->filename = $filename;
    }

    /**
     * Get mime-type to be sent with the file
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Get name of the file to be used in the upload data
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Internal method: Do not use directly!
     */
    public function createCurlFileInstance(string $defaultMimeType): CURLFile
    {
        $mimeType = $this->mimeType ?? $defaultMimeType;
        return new CURLFile($this->realFilePath, $mimeType, $this->filename);
    }

    /**
     * Internal method: Do not use directly!
     */
    public function getContent(): ?string
    {
        $thisFile = new SplFileObject($this->realFilePath);
        $content = $thisFile->fread($thisFile->getSize());
        return $content === false ? null : $content;
    }
}