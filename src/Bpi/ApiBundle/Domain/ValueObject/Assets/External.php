<?php

namespace Bpi\ApiBundle\Domain\ValueObject\Assets;



/**
 * Bpi\ApiBundle\Domain\ValueObject\Assets\External
 */
class External
{
    /**
     * @var string $external
     */
    protected $external;


    /**
     * Set external
     *
     * @param string $external
     * @return self
     */
    public function setExternal($external)
    {
        $this->external = $external;
        return $this;
    }

    /**
     * Get external
     *
     * @return string $external
     */
    public function getExternal()
    {
        return $this->external;
    }
}
