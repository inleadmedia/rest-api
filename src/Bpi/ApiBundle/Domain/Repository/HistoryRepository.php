<?php
namespace Bpi\ApiBundle\Domain\Repository;

use Bpi\ApiBundle\Domain\Aggregate\TitleWrapperInterface;
use Bpi\ApiBundle\Domain\Entity\History;
use Bpi\ApiBundle\Domain\Entity\StatisticsExtended;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Bpi\ApiBundle\Domain\Aggregate\Agency;
use Bpi\ApiBundle\Domain\Aggregate\Node;
use Bpi\ApiBundle\Domain\Entity\Statistics;

/**
 * HistoryRepository
 *
 */
class HistoryRepository extends DocumentRepository
{
  public function getStatisticsByDateRangeForAgency($dateFrom, $dateTo, $agencyId)
  {
    $dateFrom = new \DateTime($dateFrom . ' 00:00:00');
    $dateTo = new \DateTime($dateTo . ' 23:59:59');

    $qb = $this->createQueryBuilder()
        ->field('datetime')->gte($dateFrom)
        ->field('datetime')->lte($dateTo);

    if (!empty($agencyId)) {
        $qb->field('agency')->equals($agencyId);
    }

    $qb->map('function() { emit(this.action, 1); }')
        ->reduce('function(k, vals) {
            var sum = 0;
            for (var i in vals) {
                sum += vals[i];
            }
            return sum;
        }');
    $result = $qb->getQuery()->execute();

    $res = array();
    foreach ($result as $r) {
      $res[$r['_id']] = $r['value'];
    }

    return new Statistics($res);
  }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param $actionFilter
     * @param $aggregateField
     * @param array $agencyFilter
     * @param int $limit
     *
     * @return \Bpi\ApiBundle\Domain\Entity\StatisticsExtended
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getActivity(\DateTime $dateFrom, \DateTime $dateTo, $actionFilter, $aggregateField, $agencyFilter = [], $limit = 10) {
        $qb = $this->createQueryBuilder()
            ->field('datetime')
                ->gte($dateFrom)
                ->lte($dateTo)
            ->field('action')
                ->equals($actionFilter);

        if ('node' == $aggregateField && !empty($agencyFilter)) {
            $nodeFilterResults = $this->dm->createQueryBuilder(Node::class)
                ->field('author.agency_id')
                ->in($agencyFilter)
                ->getQuery()
                ->execute();

            $filterIds = [];
            /** @var \Bpi\ApiBundle\Domain\Aggregate\Node $node */
            foreach ($nodeFilterResults as $node) {
                $filterIds[] = new \MongoId($node->getId());
            }

            $qb
                ->field('node.$id')
                ->in($filterIds);
        }

        $results = $qb->getQuery()->execute();

        $activity = [];
        foreach ($results as $result) {
            $entityMethod = 'get' . ucfirst(strtolower($aggregateField));
            $aggregateFieldResult = $result->{$entityMethod}();
            $aggregateId = is_string($aggregateFieldResult) ? $aggregateFieldResult : $aggregateFieldResult->getId();
            $activity[$aggregateId]['id'] = $aggregateId;
            $activity[$aggregateId]['title'] = 'node' == $aggregateField ? $this->getNodeTitle($aggregateId) : $this->getAgencyTitle($aggregateId);
            if (!isset($activity[$aggregateId]['total'])) {
                $activity[$aggregateId]['total'] = 0;
            }
            $activity[$aggregateId]['total']++;
        }

        usort($activity, function ($a, $b) {
            return $a['total'] < $b['total'];
        });

        $activity = array_slice($activity, 0, $limit);

        return new StatisticsExtended(
            $dateFrom,
            $dateTo,
            $actionFilter,
            $aggregateField,
            $activity
        );
    }

    /**
     * Gets node title.
     *
     * @param string $id
     *   Node internal id.
     *
     * @return string
     *   Node title.
     */
    private function getNodeTitle($id) {
        $dm = $this->dm;

        $entity = $dm
            ->getRepository(Node::class)
            ->find($id);

        if ($entity instanceof TitleWrapperInterface) {
            return $entity->getTitle();
        }

        return (string) $entity;
    }

    /**
     * Gets agency title.
     *
     * @param string $id
     *   Agency public id.
     *
     * @return string
     *   Agency name.
     */
    private function getAgencyTitle($id) {
        $dm = $this->dm;

        $entity = $dm
            ->getRepository(Agency::class)
            ->findOneBy([
                'public_id' => $id,
            ]);

        if ($entity instanceof TitleWrapperInterface) {
            return $entity->getTitle();
        }

        return (string) $entity;
    }
}
