<?php

namespace Bpi\ApiBundle\Domain\ValueObject\Assets;



/**
 * Bpi\ApiBundle\Domain\ValueObject\Assets\Title
 */
class Title
{
    /**
     * @var string $title
     */
    protected $title;


    /**
     * Set title
     *
     * @param string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }
}
