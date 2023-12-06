<?php

namespace App;

class Product
{
    public string $title;
    public float $price;
    public string $imageUrl;
    public float $capacityMB;
    public string $colour;
    public string $availabilityText;
    public string $isAvailable;
    public string $shippingText;
    public string $shippingDate;

    public function equals(Product $other) : bool
    {
        return $this->title == $other->title &&
               $this->price == $other->price &&
               $this->imageUrl == $other->imageUrl &&
               $this->capacityMB == $other->capacityMB &&
               $this->colour == $other->colour &&
               $this->availabilityText == $other->availabilityText &&
               $this->isAvailable == $other->isAvailable &&
               $this->shippingText == $other->shippingText &&
               $this->shippingDate == $other->shippingDate;
    }
}
