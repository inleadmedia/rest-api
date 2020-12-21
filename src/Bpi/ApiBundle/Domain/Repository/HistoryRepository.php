<?php
namespace Bpi\ApiBundle\Domain\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Bpi\ApiBundle\Domain\Entity\Statistics;

/**
 * HistoryRepository
 *
 */
class HistoryRepository extends DocumentRepository
{
  public function getStatisticsByDateRangeForAgency(\DateTime $dateFrom, \DateTime $dateTo, $agencyId = [])
  {
    $qb = $this->createQueryBuilder()
        ->field('datetime')->gte($dateFrom)
        ->field('datetime')->lte($dateTo);

    if (!empty($agencyId)) {
        $qb->field('agency')->in($agencyId);
    }

    $qb
        ->map('function() { emit(this.action, 1); }')
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
}
