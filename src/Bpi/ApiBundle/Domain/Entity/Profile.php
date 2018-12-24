<?php

namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Transform\Comparator;
use Bpi\ApiBundle\Transform\IPresentable;
use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Domain\Entity\Profile\Relation\IRelation;
use Bpi\ApiBundle\Domain\ValueObject\Yearwheel;
use Bpi\ApiBundle\Domain\ValueObject\ValueObjectList as VOList;
use Bpi\RestMediaTypeBundle\XmlResponse;

class Profile implements IPresentable
{
    /**
     * Closed optional attribute
     *
     * @var \Bpi\ApiBundle\Domain\ValueObject\Yearwheel
     */
    protected $yearwheel;

    /**
     * Open optional dictionary
     *
     * @var \Bpi\ApiBundle\Domain\ValueObject\ValueObjectList
     */
    protected $tags;

    /**
     * @TODO
     *
     * @var mixed
     */
    protected $relations;

    public function __construct(Yearwheel $yearwheel = null, VOList $tags = null)
    {
        $this->yearwheel = $yearwheel;
        $this->tags = $tags;
        $this->relations = new \SplObjectStorage();
    }

    /**
     *
     * @param \Bpi\ApiBundle\Domain\Entity $profile
     * @param string $field
     * @param int $order 1=asc, -1=desc
     *
     * @return int see strcmp PHP function
     */
    public function compare(Profile $profile, $field, $order = 1)
    {
        if (stristr($field, '.')) {
            list($local_field, $child_field) = explode(".", $field, 2);

            return $this->$local_field->compare($profile->$local_field, $child_field, $order);
        }

        $cmp = new Comparator($this->$field, $profile->$field, $order);

        return $cmp->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function transform(XmlResponse $document)
    {
        try {
            $entity = $document->currentEntity();
        } catch (\RuntimeException $e) {
            $entity = $document->createEntity('entity', 'profile');
            $document->appendEntity($entity);
        }
        if ($this->yearwheel instanceof Yearwheel) {
            $entity->addProperty(
                $document->createProperty(
                    'yearwheel',
                    'string',
                    $this->yearwheel->name()
                )
            );
        }

        if ($this->tags && $this->tags->count()) {
            $entity->addProperty(
                $document->createProperty(
                    'tags',
                    'string',
                    implode(', ', $this->tags->toArray())
                )
            );
        }
    }

    /**
     * Set yearwheel
     *
     * @param Bpi\ApiBundle\Domain\ValueObject\Yearwheel $yearwheel
     *
     * @return self
     */
    public function setYearwheel(\Bpi\ApiBundle\Domain\ValueObject\Yearwheel $yearwheel)
    {
        $this->yearwheel = $yearwheel;

        return $this;
    }

    /**
     * Get yearwheel
     *
     * @return Bpi\ApiBundle\Domain\ValueObject\Yearwheel $yearwheel
     */
    public function getYearwheel()
    {
        return $this->yearwheel;
    }

    /**
     * Add tag
     *
     * @param $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * Remove tag
     *
     * @param $tag
     */
    public function removeTag($tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return Doctrine\Common\Collections\Collection $tags
     */
    public function getTags()
    {
        return $this->tags;
    }
}
