<?php

namespace Bpi\ApiBundle\Domain\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Bpi\ApiBundle\Domain\Entity\UserQuery;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends DocumentRepository
{
    /**
     * Find Users by a query.
     *
     * @param UserQuery $query
     *
     * @return mixed
     */
    public function findByQuery(UserQuery $query)
    {
        return $query->executeByDoctrineQuery(
            $this->dm->createQueryBuilder($this->getClassName())
        );
    }

    /**
     * Check if User with such internal name exitst
     *
     * @param string $internalName
     *
     * @return bool
     */
    public function findSimilarUserByInternalName($internalName)
    {
        $query = $this->createQueryBuilder('Entity\User')
            ->field('internalUserName')
            ->equals($internalName);

        $result = $query
            ->getQuery()
            ->getSingleResult();

        return ($result) ? true : false;
    }

    /**
     * Find user by external id and agency public id
     *
     * @param string $externalId
     * @param string $agencyPublicId
     *
     * @return array|null|object
     */
    public function findByExternalIdAgency($externalId, $agencyId)
    {
        $result = $this->findOneBy(
            [
                'userAgency.id' => $agencyId,
                'externalId' => $externalId,
            ]
        );

        return $result;
    }

    public function getUserNotifications($user)
    {
        $facetRepository = $this->dm->getRepository('BpiApiBundle:Entity\Facet');
        $nodeRepository = $this->dm->getRepository('BpiApiBundle:Aggregate\Node');
        $channelRepository = $this->dm->getRepository('BpiApiBundle:Entity\Channel');
        $channelQuery = $this->dm->createQueryBuilder('Bpi\ApiBundle\Domain\Entity\Channel');
        $nodeQuery = $nodeRepository->createQueryBuilder();
        $userSubscriptions = $user->getSubscriptions();

        $todayStart = new \DateTime('today');
        $todayStart->setTime(0, 0, 0);
        $todayEnd = new \DateTime('today');
        $todayEnd->setTime(23, 59, 59);

        //Get user notification by subscription.
        $notifications = [];
        if (!empty($userSubscriptions)) {
            foreach ($userSubscriptions as $subscription) {
                $jsonString = html_entity_decode(trim($subscription->getFilter(), '&quot;'));
                $filter = (array)json_decode($jsonString);
                $facets = $facetRepository->getFacetsByRequest($filter);
                $nodes = $facets->nodeIds;
                $nodeUpdates = $nodeQuery
                    ->addAnd($nodeQuery->expr()->field('ctime')->gte($todayStart))
                    ->addAnd($nodeQuery->expr()->field('ctime')->lte($todayEnd))
                    ->addAnd($nodeQuery->expr()->field('mtime')->gte($todayStart))
                    ->addAnd($nodeQuery->expr()->field('mtime')->lte($todayEnd))
                    ->field('_id')->in($nodes)
                    ->getQuery()
                    ->toArray();

                if (!empty($nodeUpdates)) {
                    $notifications['subscriptions'][$subscription->getTitle()] = [];
                    foreach ($nodeUpdates as $node) {
                        $notifications['subscriptions'][$subscription->getTitle()][] = $node;
                    }
                }
            }
        }

        //Get user notification by channels.
        $userChannels = $channelRepository->findChannelsByUser($user);

        if (!empty($userChannels)) {
            foreach ($userChannels as $channel) {
                $channelNodes = $channel->getChannelNodes();
                if (!empty($channelNodes)) {
                    foreach ($channelNodes as $cNode) {
                        $cTime = $cNode->getCtime();
                        $mTime = $cNode->getMtime();
                        $checkCtime = $cTime >= $todayStart && $cTime <= $todayEnd;
                        $checkMtime = $mTime >= $todayStart && $mTime <= $todayEnd;

                        if ($checkCtime && $checkMtime) {
                            if (!isset($notifications['channel'][$channel->getChannelName()])) {
                                $notifications['channel'][$channel->getChannelName()] = [];
                            }
                            $notifications['channel'][$channel->getChannelName()][] = $cNode;
                        }
                    }
                }
            }
        }

        return $notifications;
    }
}
