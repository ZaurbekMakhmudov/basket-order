<?php

namespace App\BasketOrderBundle\Helper;

use OpenTracing\Span;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TraceHelper
{
    public static function addHttpTags(ResponseInterface $response, ?Span $span)
    {
        if ($span instanceof Span) {
            $span->setTag('identifier', $response->toArray()['identifier']);
            $span->setTag('http.method', $response->getInfo()['http_method']);
            $span->setTag('http.target', $response->getInfo()['url']);
            $span->setTag('http.status_code', $response->getInfo()['http_code']);
            $span->setTag('http.status_text', Response::$statusTexts["{$response->getInfo()['http_code']}"]);
            if ($response->getInfo()['http_code'] !== Response::HTTP_OK) {
                $span->setTag('error', true);
            }
        }
    }
}