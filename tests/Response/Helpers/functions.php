<?php
namespace Selline\HttpServer\Response;

use Selline\HttpServer\Tests\Response\Helpers\HeaderStack;

/**
 * Have headers been sent?
 *
 * @param null|string $file
 * @param null|int    $line
 *
 * @return bool false
 */
function headers_sent(&$file = null, &$line = null): bool
{
    $sent = HeaderStack::$headersSent;

    if ($sent) {
        $file = HeaderStack::$headersFile;
        $line = HeaderStack::$headersLine;
    }

    return $sent;
}

/**
 * Emit a header, without creating actual output artifacts.
 *
 * @param string   $string
 * @param bool     $replace
 * @param null|int $statusCode
 *
 * @return void
 */
function header($string, $replace = true, $statusCode = null): void
{
    HeaderStack::push(
        [
            'header' => $string,
            'replace' => $replace,
            'status_code' => $statusCode,
        ]
    );
}