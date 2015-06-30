<?php

namespace Bpi\ApiBundle\Domain\ValueObject\Assets;



/**
 * Bpi\ApiBundle\Domain\ValueObject\Assets\Extension
 */
class Extension
{
    /**
     * @var string $extension
     */
    protected $extension;


    /**
     * Set extension
     *
     * @param string $extension
     * @return self
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
        return $this;
    }

    /**
     * Get extension
     *
     * @return string $extension
     */
    public function getExtension()
    {
        return $this->extension;
    }
}
