# Core Library for PHP

[![version][packagist-version]][packagist-url]
[![Downloads][packagist-downloads]][packagist-url]
[![Tests][test-badge]][test-url]
[![License][license-badge]][license-url]

## Introduction

Core logic and the utilities for the Apimatic's PHP SDK.

## Requirements
- PHP v7.2.x => v8.1.x

## Install the Package

Run the following command to install the package and automatically add the dependency to your composer.json file:

```php
composer require "apimatic/core-lib-php"
```

Or add it to the composer.json file manually as given below:

```php
"require": {
    "apimatic/core": "*"
}
```

[packagist-url]: https://packagist.org/packages/apimatic/core
[packagist-version]: https://img.shields.io/packagist/v/apimatic/core.svg?style=flat
[packagist-downloads]: https://img.shields.io/packagist/dm/apimatic/core.svg?style=flat
[license-badge]: https://img.shields.io/badge/licence-OSL--3.0-blue
[license-url]: https://opensource.org/licenses/OSL-3.0
[test-badge]: https://github.com/apimatic/core-lib-php/actions/workflows/test.yml/badge.svg
[test-url]: https://github.com/apimatic/core-lib-php/actions/workflows/test.yml

## Request
| Name                                                                        | Description                                                           |
|-----------------------------------------------------------------------------|-----------------------------------------------------------------------|
| [`AdditionalFormParams`](src/Request/Parameters/AdditionalFormParams.php)   | Used to add additional form params to a request                       |
| [`AdditionalQueryParams`](src/Request/Parameters/AdditionalQueryParams.php) | Used to add additional query params to a request                      |
| [`BodyParam`](src/Request/Parameters/BodyParam.php)                         | Body parameter class                                                  |
| [`FormParam`](src/Request/Parameters/FormParam.php)                         | Form parameter class                                                  |
| [`HeaderParam`](src/Request/Parameters/HeaderParam.php)                     | Header parameter class                                                |
| [`QueryParam`](src/Request/Parameters/QueryParam.php)                       | Query parameter class                                                 |
| [`TemplateParam`](src/Request/Parameters/TemplateParam.php)                 | Template parameter class                                              |
| [`Request`](src/Request/Request.php)                                        | Request class for an API call                                         |
| [`RequestBuilder`](src/Request/RequestBuilder.php)                          | Used to instantiate a new Request object with the properties provided |

## Response
| Name                                                                        | Description                                                                           |
|-----------------------------------------------------------------------------|---------------------------------------------------------------------------------------|
| [`DeserializableType`](src/Response/Types/DeserializableType.php)           | Type handler used to deserialize Enums and DateTime                                   |
| [`ErrorType`](src/Response/Types/ErrorType.php)                             | Type handler used to throw exceptions from responses                                  |
| [`ResponseMultiType`](src/Response/Types/ResponseMultiType.php)             | Maps a group of types to response body                                                |
| [`ResponseType`](src/Response/Types/ResponseType.php)                       | Maps a model to response body                                                         |
| [`Context`](src/Response/Context.php)                                       | Holds the current context i.e. the current request, response and other needed details |
| [`ResponseError`](src/Response/ResponseError.php)                           | Group of error types for response                                                     |
| [`ResponseHandler`](src/Response/ResponseHandler.php)                       | Response handler for an API call                                                      |


## TestCase
| Name                                                                                 | Description                                                                    |
|--------------------------------------------------------------------------------------|--------------------------------------------------------------------------------|
| [`KeysAndValuesBodyMatcher`](src/TestCase/BodyMatchers/KeysAndValuesBodyMatcher.php) | Matches a body received to expected body, considering both the keys and values |
| [`KeysBodyMatcher`](src/TestCase/BodyMatchers/KeysBodyMatcher.php)                   | Matches a body received to expected body, considering just the keys of both    |
| [`NativeBodyMatcher`](src/TestCase/BodyMatchers/NativeBodyMatcher.php)               | A body matcher for native values like string, int etc                          |
| [`RawBodyMatcher`](src/TestCase/BodyMatchers/RawBodyMatcher.php)                     | Exactly matches the body received to expected body                             |
| [`HeadersMatcher`](src/TestCase/HeadersMatcher.php)                                  | Matches the headers received and the headers expected                          |
| [`StatusCodeMatcher`](src/TestCase/StatusCodeMatcher.php)                            | Matches the HTTP status codes received to the expected ones                    |
| [`CoreTestCase`](core-lib-php/src/TestCase/CoreTestCase.php)                         | Main class for a test case                                                     |
