<?php
namespace Bpi\ApiBundle\Domain\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

class CategoryRepository extends DocumentRepository
{
    /**
     * Show all categories.
     *
     * @param string $param
     * @param string $direction
     * @param boolean $disabled
     *
     * @return array
     */
    public function listAll($param = null, $direction = null, $disabled = null)
    {
        $qb = $this->createQueryBuilder();

        if ($param && $direction)
        {
            $qb->sort($param, $direction);
        }

        if (null !== $disabled) {
            $qb->field('disabled')->equals((boolean) $disabled);
        }

        return $qb;
    }


    public function save($category)
    {
        $this->dm->persist($category);
        $this->dm->flush($category);
    }
}
