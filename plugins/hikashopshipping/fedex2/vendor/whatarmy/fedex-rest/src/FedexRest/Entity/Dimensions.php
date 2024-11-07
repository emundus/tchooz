<?php

namespace FedexRest\Entity;

class Dimensions
{
    public ?float $width;
    public ?float $height;
    public ?float $length;
    public string $units = '';

    public function setLength(float $length): Dimensions
    {
        $this->length = $length;
        return $this;
    }

    public function setWidth(float $width): Dimensions
    {
        $this->width = $width;
        return $this;
    }

    public function setHeight(float $height): Dimensions
    {
        $this->height = $height;
        return $this;
    }

    public function setUnits(string $units): Dimensions
    {
        $this->units = $units;
        return $this;
    }

    public function prepare(): array
    {
        $dimensions = [];
        if (!empty($this->length)) {
            $dimensions['length'] = $this->length;
        }
        if (!empty($this->width)) {
            $dimensions['width'] = $this->width;
        }
        if (!empty($this->height)) {
            $dimensions['height'] = $this->height;
        }
        if (!empty($this->units)) {
            $dimensions['units'] = $this->units;
        }
        return $dimensions;
    }

}
