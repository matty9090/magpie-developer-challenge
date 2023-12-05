<?php

namespace App;

require 'vendor/autoload.php';
require 'Product.php';

use Symfony\Component\DomCrawler\Crawler;

class Scrape
{
    private const CURRENCY_SYMBOL = '£';

    private array $products = [];

    public function run() : void
    {
        $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones');

        $this->products = $document->filter('.product')->each(function (Crawler $node, $i) : Product {
            $name = "";
            if (!$this->getProductName($node, $name))
            {
                echo 'Failed to get product name\n';
                return null;
            }
            
            $price = 0.0;
            if (!$this->getProductPrice($node, $price))
            {
                echo 'Failed to get product price\n';
                return null;
            }
            
            return new Product($name, $this->parsePrice($price));
        });

        $this->removeNull();
        $this->removeDuplicates();
        
        file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT));
    }

    private function getProductName($node, &$name)
    {
        $filtered = $node->filter('h3');
        $success = $filtered->count() >= 0;

        if ($success)
        {
            $name = $filtered->text();
        }

        return $success;
    }

    private function getProductPrice($node, &$price)
    {
        $filtered = $node->filter('div div')->reduce(function (Crawler $price_node, $j) : bool {
            return str_contains($price_node->text(), '£');
        });

        $success = $filtered->count() >= 0;

        if ($success)
        {
            $price = $filtered->last()->text();
        }

        return $success;
    }

    private function parsePrice($price_str)
    {
        return doubleval(str_replace(self::CURRENCY_SYMBOL, '', $price_str));
    }

    private function removeNull()
    {
        $this->products = array_filter($this->products, function($product) {
            return $product != null;
        });
    }

    private function removeDuplicates()
    {
        $this->products = array_filter($this->products, function($product) {
            foreach ($this->products as &$other)
            {
                if ($product != $other && $product->name == $other->name)
                {
                    return false;
                }
            }

            return true;
        });
    }
}

$scrape = new Scrape();
$scrape->run();
