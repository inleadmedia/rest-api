<?php
namespace Bpi\ApiBundle\Domain\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AgencyRepository extends DocumentRepository implements UserProviderInterface
{
    /**
     * {@inheritdoc}
     *
     * @param string $agencyId find agency by public id
     */
    public function loadUserByUsername($agencyId)
    {
        return $this->findOneBy(array('public_id' => $agencyId));
    }

    public function refreshUser(UserInterface $user)
    {

    }

    public function supportsClass($class)
    {
        // @todo Add a proper check?
    }

    /**
     * Show all agencies filtered by "deleted" value.
     *
     * @param string $param
     * @param string $direction
     * @param bool $deleted
     * @param bool $internal
     *
     * @return array
     */
    public function listAll($param = null, $direction = null, $deleted = null, $internal = null)
    {
        $qb = $this->createQueryBuilder();

        if ($param && $direction) {
            $qb->sort($param, $direction);
        }

        if (null !== $deleted) {
            $qb->field('deleted')->equals((boolean)$deleted);
        }

        if (null !== $internal) {
            $qb->field('internal')->equals((boolean)$internal);
        }

        return $qb;
    }

    /**
     * Delete an agency
     *
     * @param string $id Agency ID
     */
    public function delete($id)
    {
        $agency = $this->find($id);
        $agency->setDeleted();
        $this->dm->persist($agency);
        $this->dm->flush($agency);
    }

    /**
     * Purge an agency (permanent delete).
     *
     * @param string $id Agency ID
     */
    public function purge($id)
    {
        $agency = $this->find($id);
        $this->dm->remove($agency);
        $this->dm->flush();
    }

    /**
     * Restore deleted agency
     *
     * @param string $id AgencyID
     */
    public function restore($id)
    {
        $agency = $this->find($id);
        $agency->setDeleted(false);
        $this->dm->persist($agency);
        $this->dm->flush($agency);
    }

    public function save($agency)
    {
        $this->dm->persist($agency);
        $this->dm->flush($agency);
    }
}
