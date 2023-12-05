<?php

namespace App;

class Product
{
    public string $name;
    public string $price;

    public function __construct($name, $price)
    {
        $this->name = $name;
        $this->price = $price;
    }
}
