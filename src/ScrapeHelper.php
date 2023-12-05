<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeHelper
{
    public static function fetchDocument(string $url): Crawler
    {
        $client = new Client();

        $response = $client->get($url);

        return new Crawler($response->getBody()->getContents(), $url);
    }

    /* Adapted from: https://stackoverflow.com/a/25778430 */
    public static function convertRelativeUrlToAbsolute($rel, $base)
    {
        /* parse base URL and convert to local variables:
        $scheme, $host, $path */
        extract(parse_url($base));

        /* remove non-directory element from path */
        $path = preg_replace( '#/[^/]*$#', '', $path );

        /* destroy path if relative url points to root */
        if( $rel[0] == '/' )
            $path = '';

        /* dirty absolute URL */
        $abs = $host . $path . '/' . $rel;

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for( $n=1; $n>0; $abs=preg_replace( $re, '/', $abs, -1, $n ) ) {}

        /* absolute URL is ready! */
        return( $scheme.'://'.$abs );
    }
}
