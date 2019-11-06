<?php

namespace Bpi\ApiBundle\Domain\Entity;

/**
 * Bpi\ApiBundle\Domain\Entity\File
 */
class File
{

    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string $title
     */
    protected $title;


    /**
     * @var string $external
     */
    protected $external;

    /**
     * @var string $alt
     */
    protected $alt;

    /**
     * @var string $extension
     */
    protected $extension;

    /**
     * @var int $width
     */
    protected $width;

    /**
     * @var int $height
     */
    protected $height;

    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->external = !empty($params['path']) ? $params['path'] : null;
        $this->name = !empty($params['name']) ? md5($params['name'].time()) : null;
        $this->title = !empty($params['title']) ? $params['title'] : null;
        $this->alt = !empty($params['alt']) ? $params['alt'] : null;
        $this->extension = !empty($params['extension']) ? $params['extension'] : null;
        $this->type = !empty($params['type']) ? $params['type'] : null;
        $this->width = !empty($params['width']) ? $params['width'] : null;
        $this->height = !empty($params['height']) ? $params['height'] : null;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string $path
     */
    public function getPath()
    {
        return $this->path;
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

    /**
     * Set type
     *
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Set title
     *
     * @param string $title
     *
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

    /**
     * Set external
     *
     * @param string $external
     *
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


    /**
     * Set alt
     *
     * @param string $alt
     *
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

    /**
     * Set extension
     *
     * @param string $extension
     *
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

    /**
     * Set width
     *
     * @param int $width
     *
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
     * @return int $width
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param int $height
     *
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
     * @return int $height
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(XmlResponse $document)
    {
        try {
            $entity = $document->currentEntity();
        } catch (\RuntimeException $e) {
            $entity = $document->createEntity('entity', 'File');
            $document->appendEntity($entity);
        }

        $entity->addProperty($document->createProperty('name', 'string', $this->getName()));
        $entity->addProperty($document->createProperty('title', 'string', $this->getTitle()));
        $entity->addProperty($document->createProperty('extension', 'string', $this->getExtension()));
        $entity->addProperty($document->createProperty('external', 'string', $this->getExternal()));
        $entity->addProperty($document->createProperty('type', 'string', $this->getType()));
        $entity->addProperty($document->createProperty('width', 'string', $this->getWidth()));
        $entity->addProperty($document->createProperty('height', 'string', $this->getHeight()));
    }
}
