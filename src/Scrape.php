<?php

namespace App;

require 'vendor/autoload.php';
require 'Product.php';

use Symfony\Component\DomCrawler\Crawler;

class Scrape
{
    private const URL = 'https://www.magpiehq.com/developer-challenge/smartphones';
    
    private const CURRENCY_SYMBOL = '£';

    private const AVAILABILITY_IN_STOCK = 'In Stock';
    private const AVAILABILITY_STR_TEST = 'Availability:';

    private const UNITS_MB = [
        'PB' => 1000 * 1000 * 1000, // Maybe one day ;)
        'TB' => 1000 * 1000,
        'GB' => 1000,
        'MB' => 1,
        'KB' => 1 / 1000
    ];

    private array $products = [];

    public function run() : void
    {
        $this->products = [];

        $document = ScrapeHelper::fetchDocument(self::URL);

        $document->filter('.product')->each(function (Crawler $node, $i) {
            $title = "";
            if (!$this->getProductTitle($node, $title))
            {
                echo "Failed to get product title\n";
                return;
            }
            
            $price = 0.0;
            if (!$this->getProductPrice($node, $price))
            {
                echo "Failed to get product price\n";
                return;
            }

            $imageUrl = "";
            if (!$this->getProductImageUrl($node, $imageUrl))
            {
                echo "Failed to get product image url\n";
                return;
            }

            $capacity = 0;
            if (!$this->getProductCapacity($title, $capacity))
            {
                echo "Failed to get product capacity\n";
                return;
            }

            $colours = [];
            if (!$this->getProductColours($node, $colours))
            {
                echo "Failed to get product colours\n";
                return;
            }

            $isAvailable = false;
            $availabilityText = [];
            if (!$this->getProductAvailability($node, $isAvailable, $availabilityText))
            {
                echo "Failed to get product availability\n";
                return;
            }

            foreach ($colours as $colour)
            {
                $product = new Product();
                $product->title = $title;
                $product->price = $this->parsePrice($price);
                $product->imageUrl = $imageUrl;
                $product->capacityMB = $capacity;
                $product->isAvailable = $isAvailable;
                $product->availabilityText = $availabilityText;

                $this->products[] = $product;
            }
        });

        $this->removeDuplicates();
        
        file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT));
    }

    private function getProductTitle($node, &$title)
    {
        $filtered = $node->filter('h3');
        $success = $filtered->count() >= 0;

        if ($success)
        {
            $title = $filtered->text();
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

    private function getProductImageUrl($node, &$imageUrl)
    {
        $filtered = $node->filter('img');
        $success = $filtered->count() >= 0;

        if ($success)
        {
            $src = $filtered->first()->attr('src');
            $imageUrl = ScrapeHelper::convertRelativeUrlToAbsolute($src, self::URL);
        }

        return $success;
    }

    private function getProductCapacity($title, &$capacity)
    {
        $matches = [];

        if (preg_match("#(\d+)(\s+)?(GB|MB|KB)#", $title, $matches))
        {
            $raw_capacity = $matches[1];
            $unit = $matches[3];
            $capacity = self::UNITS_MB[$unit] * $raw_capacity;

            return true;
        }

        return false;
    }

    private function getProductColours($node, &$colours)
    {
        $colours = $node->filter('span[data-colour]')->each(function (Crawler $col_node, $i) : string {
            return strtolower($col_node->attr('data-colour'));
        });

        return count($colours) >= 0;
    }

    private function getProductAvailability($node, &$isAvailable, &$availabilityText)
    {
        $filtered = $node->filter('div div')->reduce(function (Crawler $avail_node, $j) : bool {
            return str_contains($avail_node->text(), self::AVAILABILITY_STR_TEST);
        });

        if ($filtered->count() < 0)
        {
            return false;
        }

        $txt = $filtered->last()->text();
        $isAvailable = str_contains($txt, self::AVAILABILITY_IN_STOCK);
        $availabilityText = str_replace(self::AVAILABILITY_STR_TEST . ' ', '', $txt);

        return true;
    }

    private function parsePrice($price_str)
    {
        return doubleval(str_replace(self::CURRENCY_SYMBOL, '', $price_str));
    }

    private function removeDuplicates()
    {
        $this->products = array_filter($this->products, function($product) {
            foreach ($this->products as &$other)
            {
                if ($product != $other && $product->equals($other))
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
