<?php
namespace Bpi\ApiBundle\Domain\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Bpi\ApiBundle\Domain\Entity\NodeQuery;

class NodeRepository extends DocumentRepository
{
    public function findByNodesQuery(NodeQuery $query)
    {
        return $query->executeByDoctrineQuery(
            $this->dm->createQueryBuilder($this->getClassName())
        );
    }

    public function getNode($id)
    {
        return $this->findOneBy(array('id' => $id, 'deleted' => false));
    }

    public function delete($id, $agencyId)
    {
      // @todo Check if node was not deleted before.
      $node = $this->find($id);

      if ($node->getAgencyId() == $agencyId || $agencyId == 'ADMIN')
      {
          $node->setDeleted();
          $this->dm->persist($node);
          $this->dm->flush($node);
          return $node;
      }

      return null;
    }

    public function restore($id, $agencyId)
    {
      // @todo Check if node was not deleted before.
      $node = $this->find($id);

      if ($node->getAgencyId() == $agencyId || $agencyId == 'ADMIN')
      {
          $node->setDeleted(false);
          $this->dm->persist($node);
          $this->dm->flush($node);
          return $node;
      }

      return null;
    }

    /**
     * Show all nodes filtered by "deleted" value.
     *
     * @param string $param
     * @param string $direction
     * @param string $search
     * @param bool $deleted
     * @return array
     */
    public function listAll($param = null, $direction = null, $search = null, $deleted = false)
    {
       $qb = $this->createQueryBuilder();

        if ($param && $direction)
        {
            $qb->sort($param, $direction);
        }

          $qb->field('deleted')->equals($deleted);

       if ($search)
       {
            $qb->addOr($qb->expr()->field('resource.title')->equals(new \MongoRegex('/.*' . $search . '.*/')));
            $qb->addOr($qb->expr()->field('author.agency_id')->equals(new \MongoRegex('/.*' . $search . '.*/')));
            $qb->addOr($qb->expr()->field('resource.body')->equals(new \MongoRegex('/.*' . $search . '.*/')));
            $qb->addOr($qb->expr()->field('resource.teaser')->equals(new \MongoRegex('/.*' . $search . '.*/')));
            $qb->addOr($qb->expr()->field('resource.type')->equals(new \MongoRegex('/.*' . $search . '.*/')));
            $qb->addOr($qb->expr()->field('author.firstname')->equals(new \MongoRegex('/.*' . $search . '.*/')));
            $qb->addOr($qb->expr()->field('author.lastname')->equals(new \MongoRegex('/.*' . $search . '.*/')));
        }

      return $qb;
    }

    public function save($node)
    {
        $this->dm->persist($node);
        $this->dm->flush($node);
    }

    public function incrementSyndications($nodeId)
    {
        $qb = $this->createQueryBuilder();

        $qb
            ->field('_id')
            ->equals(new \MongoId($nodeId))
            ->field('syndications')
            ->inc(1)
            ->getQuery()
            ->execute()
        ;

        return;
    }
}
