<?php

namespace Bpi\ApiBundle\Domain\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends DocumentRepository
{
    /**
     * Check if User with such internal name exitst
     *
     * @param string $internalName
     * @return bool
     */
    public function findSimilarUserByInternalName($internalName)
    {
        $query = $this->createQueryBuilder('Entity\User')
            ->field('internalUserName')
            ->equals($internalName)
        ;

        $result = $query
            ->getQuery()
            ->getSingleResult()
        ;

        return ($result) ? true : false;
    }

    /**
     * Find user by external id and agency public id
     *
     * @param string $externalId
     * @param string $agencyPublicId
     * @return array|null|object
     */
    public function findByExternalIdAgency($externalId, $agencyId)
    {
        $result = $this->findOneBy(
            array(
                'userAgency.id' => $agencyId,
                'externalId' => $externalId
            )
        );

        return $result;
    }

    /**
     * Get list of users.
     *
     * @param $agencyId - optional filter
     * @return ArrayCollection
     */
    public function getListAutocompletions($userIternalName, $agencyId = null)
    {
        $query = $this->createQueryBuilder('Entity\User');

        if ($agencyId) {
            $query->field('userAgency.id')->equals($agencyId);
        }
        if ($userIternalName) {
            $query->field('internalUserName')->equals($userIternalName);
        }

        return $query
            ->getQuery()
            ->execute();
        ;
    }
}
