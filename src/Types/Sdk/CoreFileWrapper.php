<?php

declare(strict_types=1);

namespace CoreLib\Types\Sdk;

use CURLFile;
use SplFileObject;

class CoreFileWrapper implements \JsonSerializable
{
    /**
     * Downloads and gets a local path to a file URL.
     * Subsequent calls to the same URL will get the cached file.
     *
     * @param string $url URL of the file to download
     * @return string Local path to the file
     */
    public static function getDownloadedRealFilePath(string $url): string
    {
        $realFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "sdktests" . sha1($url) . "tmp";
        if (!file_exists($realFilePath)) {
            file_put_contents($realFilePath, fopen($url, 'r'));
        }
        return $realFilePath;
    }

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
    public function createCurlFileInstance(string $defaultMimeType = 'application/octet-stream'): CURLFile
    {
        $mimeType = $this->mimeType ?? $defaultMimeType;
        return new CURLFile($this->realFilePath, $mimeType, $this->filename);
    }

    #[\ReturnTypeWillChange] // @phan-suppress-current-line PhanUndeclaredClassAttribute for (php < 8.1)
    public function jsonSerialize()
    {
        $thisFile = new SplFileObject($this->realFilePath);
        $content = $thisFile->fread($thisFile->getSize());
        return $content === false ? null : $content;
    }
}
