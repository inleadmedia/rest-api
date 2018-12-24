<?php

namespace Bpi\ApiBundle\Domain\ValueObject;

use Bpi\ApiBundle\Transform\IPresentable;
use Bpi\RestMediaTypeBundle\XmlResponse;

class Tag implements IValueObject, IPresentable
{
    /**
     *
     * @var string
     */
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of tag
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->name();
    }

    /**
     * @param \Bpi\ApiBundle\Domain\ValueObject\Tag $tag
     *
     * @return boolean
     */
    public function equals(IValueObject $tag)
    {
        if (get_class($this) != get_class($tag)) {
            return false;
        }

        return $this->name() == $tag->name();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Bpi\RestMediaTypeBundle\Document $document
     */
    public function transform(XmlResponse $document)
    {
        $document->currentEntity()->addProperty($document->createProperty($this->name, 'yearwheel', $this->name));
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }
}
