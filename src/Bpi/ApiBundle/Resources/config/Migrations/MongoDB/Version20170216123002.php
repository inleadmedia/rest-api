<?php

namespace Bpi\ApiBundle\Migrations\MongoDB;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use Doctrine\MongoDB\Database;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration script will remove information about revision from Node entity.
 */
class Version20170216123002 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return "Migration script will remove information about revision from Node entity.";
    }

    public function up(Database $db)
    {
        $nodes = $db->selectCollection('Node');
        $nodes->createQueryBuilder('Bpi\ApiBundle\Domain\Aggregate\Node')
            ->update()
            ->multiple(true)
            ->field('level')->unsetField()->exists(true)
            ->field('path')->unsetField()->exists(true)
            ->getQuery()
            ->execute()
        ;

        $this->analyze($nodes);
    }

    public function down(Database $db)
    {
        // I don't see any reason to make DOWN migration.
    }
}
