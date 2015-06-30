<?php

namespace Bpi\ApiBundle\Domain\ValueObject\Assets;



/**
 * Bpi\ApiBundle\Domain\ValueObject\Assets\Alt
 */
class Alt
{
    /**
     * @var string $alt
     */
    protected $alt;


    /**
     * Set alt
     *
     * @param string $alt
     * @return self
     */
    public function setAlt($alt)
    {
        $this->alt = $alt;
        return $this;
    }

    /**
     * Get alt
     *
     * @return string $alt
     */
    public function getAlt()
    {
        return $this->alt;
    }
}
