<?php

namespace Bpi\ApiBundle\Domain\Entity;



/**
 * Bpi\ApiBundle\Domain\Entity\Tag
 */
class Tag
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $category
     */
    protected $category;


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return self
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Get category
     *
     * @return string $category
     */
    public function getCategory()
    {
        return $this->category;
    }
    /**
     * @var string $tag
     */
    protected $tag;


    /**
     * Set tag
     *
     * @param string $tag
     * @return self
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * Get tag
     *
     * @return string $tag
     */
    public function getTag()
    {
        return $this->tag;
    }
}
