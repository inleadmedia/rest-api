<?php
namespace Bpi\RestMediaTypeBundle\Element;

use JMS\Serializer\Annotation as Serializer;
use Bpi\RestMediaTypeBundle\Element\Property;
use Bpi\RestMediaTypeBundle\Element\Link;
use Bpi\RestMediaTypeBundle\Element\Scope\HasLinks;
use Bpi\RestMediaTypeBundle\Document;

/**
 * @Serializer\XmlRoot("item")
 */
class Entity implements HasLinks
{
    /**
     * Item without name
     */
    const NONAME = 'noname';

    /**
     * @Serializer\Exclude
     */
    protected $document;

    /**
     * @Serializer\XmlAttribute
     * @Serializer\Type("string")
     */
    protected $type;

    /**
     * @Serializer\XmlAttribute
     * @Serializer\Type("string")
     */
    protected $name;

    /**
     * @Serializer\XmlList(entry="link")
     * @Serializer\Type("array<Bpi\RestMediaTypeBundle\Element\Link>")
     */
    protected $links;

    /**
     * @Serializer\XmlList(entry="property")
     * @Serializer\Type("array<Bpi\RestMediaTypeBundle\Element\Property>")
     */
    protected $properties;

    /**
     * @Serializer\XmlList(inline=true, entry="entity")
     * @Serializer\Type("array<Bpi\RestMediaTypeBundle\Element\Entity>")
     */
    protected $entities;

    /**
     * @Serializer\Type("Bpi\RestMediaTypeBundle\Element\Assets")
     */
    protected $assets;

    /**
     * @Serializer\Type("Bpi\RestMediaTypeBundle\Element\Hypermedia")
     */
    protected $hypermedia;

    /**
     * @Serializer\Type("Bpi\RestMediaTypeBundle\Element\Tags")
     */
    protected $tags;

    /**
     *
     * @param string $type
     */
    public function __construct($type, $name = null)
    {
        $this->type = $type;
        $this->name = $name ? $name : self::NONAME;
        $this->assets = new Assets();
    }

    /**
     * Entity name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @param \Bpi\RestMediaTypeBundle\Element\Property $property
     */
    public function addProperty(Property $property)
    {
        $this->properties[] = $property;
    }

    /**
     *
     * @param array $properties
     */
    public function addProperties(array $properties)
    {
        foreach ($properties as $property) {
            $this->addProperty($property);
        }
    }

    /**
     *
     * @param \Bpi\RestMediaTypeBundle\Element\Link $link
     * @return \Bpi\RestMediaTypeBundle\Element\Entity
     */
    public function addLink(Link $link)
    {
        $this->links[] = $link;
        return $this;
    }

    /**
     *
     * @param string $name
     * @return Property|array|null
     */
    public function property($name)
    {
        $property = array();
        foreach ($this->properties as $prop) {
            if ($prop->getName() == $name) {
                $property[] = $prop;
            }
        }

        $count = count($property);
        if ($count == 0) {
            return null;
        }

        if ($count == 1) {
            return current($property);
        }

        return $property;
    }

    /**
     * @Serializer\PostDeserialize
     */
    protected function rebuildKeys()
    {
        foreach ($this->entities as $key => $entity) {
            unset($this->entities[$key]);
            $this->entities[$entity->getName()] = $entity;
        }
    }

    /**
     * Check existence of property. There might be multiple properties with same name.
     *
     * @param  string       $name
     * @param  string|null  $type
     * @return boolean
     */
    public function hasProperty($name, $type = null)
    {
        foreach ($this->properties as $key => $prop) {
            if ($prop->getName() == $name) {
                if (!is_null($type)) {
                    if (!$prop->typeOf($type)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Attach this entity to document
     *
     * @param \Bpi\RestMediaTypeBundle\Document $document
     */
    public function attach(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Check if givendocument is owner of current entity
     *
     * @param \Bpi\RestMediaTypeBundle\Document $document
     * @return bool
     */
    public function isOwner(Document $document)
    {
        return spl_object_hash($this->document) == spl_object_hash($document);
    }

    /**
     *
     * @param \Bpi\RestMediaTypeBundle\Element\Entity $entity
     */
    public function addChildEntity(Entity $entity)
    {
        $this->document->setCursorOnEntity($entity);
        $this->entities[$entity->getName()] = $entity;
    }

    /**
     *
     * @param string $name of entity
     * @return Entity
     */
    public function getChildEntity($name)
    {
        return $this->entities[$name];
    }

    /**
     * Get properties matched by name
     *
     * @param string $regexp name regular expression
     * @return array
     */
    public function matchProperties($regexp)
    {
        $props = array();
        foreach ($this->properties as $property) {
            if (preg_match($regexp, $property->getName(), $matches)) {
                $props[$matches[1]] = $property;
            }
        }
        return $props;
    }

    /**
     *
     * @param \Closure $callback with value, index arguments
     */
    public function walk(\Closure $callback)
    {
        return array_walk($this->properties, $callback);
    }

    /**
     * Get properties matched by type
     *
     * @param string $type
     * @return array
     */
    public function matchPropertiesByType($type)
    {
        $props = array();
        foreach ($this->properties as $property) {
            if ($property->typeOf($type)) {
                $props[$property->getName()] = $property;
            }
        }
        return $props;
    }

    /**
     * Adds file to assets array.
     *
     * @param mixed $data
     */
    public function addAsset($data)
    {
        $this->assets->add(new File($data));
    }

    /**
     *
     * @param \Bpi\RestMediaTypeBundle\Element\Hypermedia $controls
     * @return \Bpi\RestMediaTypeBundle\Element\Entity
     */
    public function setHypermedia(Hypermedia $controls)
    {
        $this->hypermedia = $controls;
        return $this;
    }

    /**
     * @param \Bpi\RestMediaTypeBundle\Element\Tags $tags
     * @return \Bpi\RestMediaTypeBundle\Element\Entity
     */
    public function setTags(Tags $tags)
    {
        $this->tags = $tags;
        return $this;
    }
}
