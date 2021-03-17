<?php
namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Transform\IPresentable;

class Category implements IPresentable
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $category;

    /**
     * @var boolean
     */
    protected $disabled;

    /**
     * @param string $category
     */
    public function __construct($category = null)
    {
        $this->setDisabled(false);
        $this->setCategory($category);
    }

    /**
     * Set category.
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Get category name.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Get category ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets disabled value.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Sets disabled value.
     *
     * @param boolean $disabled Parameter value.
     *
     * @return self
     */
    public function setDisabled($disabled)
    {
        $this->disabled = (boolean) $disabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Bpi\RestMediaTypeBundle\Document $document
     */
    public function transform(\Bpi\RestMediaTypeBundle\Document $document)
    {
        $entity = $document->createEntity('category');
        $document->appendEntity($entity);

        $entity->addProperty($document->createProperty('group', 'string', 'category'));
        $entity->addProperty($document->createProperty('name', 'string', $this->getCategory()));
    }
}
