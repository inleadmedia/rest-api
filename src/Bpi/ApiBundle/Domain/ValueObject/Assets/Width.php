<?php

namespace Bpi\ApiBundle\Domain\ValueObject\Assets;



/**
 * Bpi\ApiBundle\Domain\ValueObject\Assets\Width
 */
class Width
{
    /**
     * @var string $width
     */
    protected $width;


    /**
     * Set width
     *
     * @param string $width
     * @return self
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Get width
     *
     * @return string $width
     */
    public function getWidth()
    {
        return $this->width;
    }
}
