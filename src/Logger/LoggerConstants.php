<?php

namespace Core\Logger;

use Psr\Log\LogLevel;

class LoggerConstants
{
    public const ALLOWED_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG
    ];
    public const METHOD = 'method';
    public const URL = 'url';
    public const QUERY_PARAMETER = 'queryParameter';
    public const HEADERS = 'headers';
    public const BODY = 'body';
    public const STATUS_CODE = 'statusCode';
    public const CONTENT_LENGTH = 'contentLength';
    public const CONTENT_TYPE = 'contentType';
    public const CONTENT_LENGTH_HEADER = 'content-length';
    public const CONTENT_TYPE_HEADER = 'content-type';
}
