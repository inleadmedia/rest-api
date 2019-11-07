<?php
namespace Bpi\ApiBundle\Domain\Service;


use Doctrine\Common\Persistence\ObjectManager;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Bpi\ApiBundle\Domain\Entity\Profile;
use Bpi\ApiBundle\Domain\Entity\Author;
use Bpi\ApiBundle\Domain\Entity\Resource;
use Bpi\ApiBundle\Domain\Entity\Category;
use Bpi\ApiBundle\Domain\Entity\Audience;
use Bpi\ApiBundle\Domain\Aggregate\Params;
use Bpi\ApiBundle\Domain\ValueObject\Copyleft;
use Bpi\ApiBundle\Domain\ValueObject\NodeId;
use Bpi\ApiBundle\Domain\ValueObject\Param\Authorship;
use Bpi\ApiBundle\Domain\Factory\NodeBuilder;
use Bpi\ApiBundle\Domain\Factory\ResourceBuilder;
use Bpi\ApiBundle\Domain\Entity\History;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Domain service for content syndication
 */
class PushService
{
    /**
     *
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $manager;
    /**
     *
     * @var \Gaufrette\FilesystemMap
     */
    protected $fs_map;

    /**
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     * @param \Knp\Bundle\GaufretteBundle\FilesystemMap $fs_map
     */
    public function __construct(ObjectManager $manager, FilesystemMap $fs_map)
    {
        $this->manager = $manager;
        $this->fs_map = $fs_map;
    }

    /**
     *
     * @param  \Bpi\ApiBundle\Domain\Entity\Author $author
     * @param  Resource $resource
     * @param  \Bpi\ApiBundle\Domain\Entity\Profile $profile
     * @param  \Bpi\ApiBundle\Domain\Aggregate\Params $params
     * @throws \LogicException
     * @return \Bpi\ApiBundle\Domain\Aggregate\Node
     */
    public function push(
        Author $author,
        ResourceBuilder $resource_builder,
        $category,
        $audience,
        Profile $profile,
        Params $params
    ) {
        $authorship = $params->filter(
            function ($e) {
                if ($e instanceof Authorship) {
                    return true;
                }
            }
        )->first();

        $this->assignCopyleft($author, $resource_builder, $authorship);
        $resource = $resource_builder->build();

        // Find dublicates
        $dublicates = $resource->findSimilar($this->manager->getRepository('BpiApiBundle:Aggregate\Node'));
        if (count($dublicates)) {
            throw new \LogicException('Found similar resource');
        }

        $builder = new NodeBuilder();
        $builder
          ->author($author)
          ->profile($profile)
          ->resource($resource)
          ->params($params);

        // Set default category.
        if (empty($category)) {
            $category = 'Other';
        }
        // Find category entity.
        $category = $this->manager->getRepository('BpiApiBundle:Entity\Category')->findOneBy(
            array('category' => $category)
        );
        $builder->category($category);

        // Set default audience.
        if (empty($audience)) {
            $audience = 'All';
        }
        // Find audience entity.
        $audience = $this->manager->getRepository('BpiApiBundle:Entity\Audience')->findOneBy(
            array('audience' => $audience)
        );
        $builder->audience($audience);

        $node = $builder->build();
        $log = new History($node, $author->getAgencyId(), new \DateTime(), 'push');

        $this->manager->getRepository('BpiApiBundle:Aggregate\Node')->save($node);
        $this->manager->persist($log);

        $this->manager->flush();
        $this->manager->getRepository('BpiApiBundle:Entity\Facet')->prepareFacet($node);

        return $node;
    }

    /**
     * Add agency copyleft to the resource
     *
     * @param \Bpi\ApiBundle\Domain\Entity\Author $author
     * @param \Bpi\ApiBundle\Domain\Factory\ResourceBuilder $builder
     * @param \Bpi\ApiBundle\Domain\ValueObject\Autorship $autorship
     */
    public function assignCopyleft(Author $author, ResourceBuilder $builder, Authorship $autorship)
    {
        $copyleft = new Copyleft;

        // Set agency as default original copyrighter
        $this->manager->getRepository('BpiApiBundle:Aggregate\Agency')
          ->findOneBy(array('public_id' => $author->getAgencyId()->id()))
          ->setAuthorship($copyleft);

        if ($autorship->isPositive()) {
            $author->setAuthorship($copyleft);
        }

        $builder->copyleft($copyleft);
    }

    /**
     *
     * @param  \Bpi\ApiBundle\Domain\ValueObject\NodeId $node_id
     * @param  \Bpi\ApiBundle\Domain\Entity\Author $author
     * @param  ResourceBuilder $builder
     * @param  \Bpi\ApiBundle\Domain\Aggregate\Params $params
     * @return \Bpi\ApiBundle\Domain\Aggregate\Node
     */
    public function pushRevision(NodeId $node_id, Author $author, ResourceBuilder $builder, Params $params)
    {
        $node = $this->manager->getRepository('BpiApiBundle:Aggregate\Node')->findOneById($node_id->id());

        $revision = $node->createRevision($author, $builder->build(), $params);

        $this->manager->persist($revision);
        $this->manager->flush();

        $this->manager->getRepository('BpiApiBundle:Entity\Facet')->prepareFacet($revision);

        return $revision;
    }

    public function getFilesystem()
    {
        return $this->fs_map->get('assets');
    }
}
