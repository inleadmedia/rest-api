<?php

namespace Bpi\ApiBundle\Domain\ValueObject\Assets;



/**
 * Bpi\ApiBundle\Domain\ValueObject\Assets\Height
 */
class Height
{
    /**
     * @var string $height
     */
    protected $height;


    /**
     * Set height
     *
     * @param string $height
     * @return self
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Get height
     *
     * @return string $height
     */
    public function getHeight()
    {
        return $this->height;
    }
}
