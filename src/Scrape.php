<?php

namespace App;

require 'vendor/autoload.php';
require 'Product.php';

use Symfony\Component\DomCrawler\Crawler;

class Scrape
{
    private array $products = [];

    public function run(): void
    {
        $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones');

        $this->products = $document->filter('.product')->each(function (Crawler $node, $i) : Product {
            $name = $node->filter('h3')->text();

            $price = $node->filter('div div')->reduce(function (Crawler $price_node, $j) : bool {
                return str_contains($price_node->text(), '£');
            })->last()->text();
            
            return new Product($name, $this->parse_price($price));
        });
        
        file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT));
    }

    private function parse_price($price_str)
    {
        return doubleval(str_replace("£", '', $price_str));
    }
}

$scrape = new Scrape();
$scrape->run();
