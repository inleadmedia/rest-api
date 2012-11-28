<?php
namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Transform\IPresentable;
use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Domain\Entity\Profile\Taxonomy;
use Bpi\ApiBundle\Domain\Entity\Profile\Relation\IRelation;
use Bpi\ApiBundle\Transform\Comparator;

class Profile implements IPresentable
{
    protected $taxonomy;
    protected $relations;

    public function __construct(Taxonomy $taxonomy)
    {
        $this->taxonomy = $taxonomy;
        $this->relations = new \SplObjectStorage();
    }

    public function addRelation(IRelation $relation)
    {
        $this->attach($relation);
    }

    /**
     *
     * @param \Bpi\ApiBundle\Domain\Entity $profile
     * @param string $field
     * @param int $order 1=asc, -1=desc
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
     * @inheritDoc
     */
    public function transform(Document $document)
    {
        $document->currentEntity()->addChildEntity(
            $document->createEntity('profile')
        );

        $this->taxonomy->transform($document);
    }
}
