<?php
namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Domain\ValueObject\Copyleft;
use Bpi\ApiBundle\Domain\ValueObject\AgencyId;
use Bpi\ApiBundle\Transform\IPresentable;
use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Transform\Comparator;
use Gaufrette\Filesystem;

/**
 * Remote resource like article, news item, etc
 */
class Resource implements IPresentable
{
    protected $title;

    protected $body;

    protected $teaser;

    protected $ctime;

    protected $type = 'article';

    protected $assets = array();

    protected $copyleft;


    protected $hash;

    protected $router;
    protected $filesystem;

    protected $materials = array();

    protected $category;
    protected $audience;

    protected $url;
    protected $data;

    /**
     *
     * @param string $title
     * @param string $body
     * @param string $teaser
     * @param Copyleft $copyleft
     * @param \DateTime $ctime
     * @param array $files
     * @param array $assets
     * @param \Gaufrette\Filesystem $filesystem
     * @param object $router
     */
    public function __construct(
        $title,
        $body,
        $teaser,
        Copyleft $copyleft,
        \DateTime $ctime,
        $category,
        $audience,
        array $files = null,
        array $assets = array(),
        Filesystem $filesystem,
        $router,
        array $materials = array(),
        $url,
        $data
    ) {
        $this->title = $title;
        $this->body = new Resource\Body($body, $filesystem, $router);
        $this->body->rebuildInlineAssets();
        $this->teaser = $teaser;
        $this->copyleft = $copyleft;
        $this->ctime = $ctime;
        $this->regenerateHash();
        $this->assets = array_merge($assets, $this->body->getAssets());
        $this->filesystem = $filesystem;
        $this->router = $router;
        $this->materials = $materials;
        $this->category = $category;
        $this->audience = $audience;
        $this->url = $url;
        $this->data = $data;
    }

    /**
     * Some data like materials are dependent of syndicator context
     *
     * @param  AgencyID $owner
     * @param  AgencyID $syndicator
     * @return void
     */
    public function defineAgencyContext(AgencyId $owner, AgencyId $syndicator)
    {
        $syndication_materials = array();
        foreach ($this->materials as $material) {
            if ($material->isLibraryEquals($owner)) {
                $syndication_materials[] = $material->reassignToAgency($syndicator);
            } else {
                $syndication_materials[] = $material;
            }
        }

        $this->materials = $syndication_materials;
    }

    /**
     * Calculate similarity of resources by checking body contents
     *
     * @param  Resource $resource
     * @return boolean
     */
    public function isSimilar(Resource $resource)
    {
        if ($this->body == $resource->body) {
            return true;
        }

        similar_text(strip_tags($this->body), strip_tags($resource->body), $similarity);
        if ($similarity > 50) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(Document $document)
    {
        try {
            $entity= $document->currentEntity();
        } catch (\RuntimeException $e) {
            $entity = $document->createEntity('entity', 'resource');
            $document->appendEntity($entity);
        }

        $copyleft = '<p>' . $this->copyleft . '</p>';

        $entity->addProperty($document->createProperty('title', 'string', $this->title));
        $entity->addProperty($document->createProperty('body', 'string', $this->body->getFlattenContent() . $copyleft));
        $entity->addProperty($document->createProperty('teaser', 'string', $this->teaser));
        $entity->addProperty($document->createProperty('creation', 'dateTime', $this->ctime));
        $entity->addProperty($document->createProperty('type', 'string', $this->type));
        $entity->addProperty($document->createProperty('url', 'string', $this->url));
        $entity->addProperty($document->createProperty('data', 'string', $this->data));

        foreach ($this->materials as $material) {
            $entity->addProperty($document->createProperty('material', 'string', (string) $material));
        }
    }

    /**
     *
     * @param  \Bpi\ApiBundle\Domain\Entity\Resource $resource
     * @param  string                                $field
     * @param  int                                   $order    1=asc, -1=desc
     * @return int                                   see strcmp PHP function
     */
    public function compare(Resource $resource, $field, $order = 1)
    {
        if (stristr($field, '.')) {
            list($local_field, $child_field) = explode(".", $field, 2);

            return $this->$local_field->compare($resource->$local_field, $child_field, $order);
        }

        $cmp = new Comparator($this->$field, $resource->$field, $order);

        return $cmp->getResult();
    }

    /**
     * This method will be invoked after the entity has been loaded from doctrine
     */
    public function wakeup()
    {
        $this->body = new Resource\Body($this->body, $this->filesystem, $this->router);
    }

    /**
     * Generate hash of the content
     *
     * @return string
     */
    protected function regenerateHash()
    {
        $this->hash = md5(strip_tags($this->title . $this->teaser . $this->body));
    }

    /**
     * Try to find similar resources
     *
     * @param \Bpi\ApiBundle\Domain\Repository\ResourceRepository $repository
     * @return array
     */
    public function findSimilar(\Doctrine\ODM\MongoDB\DocumentRepository $repository)
    {
        // @todo replace with query
        return $repository->findOneBy(array('resource.hash' => $this->hash, 'deleted' => false));
    }

    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($title)
    {
        $this->title = $title;
    }
    public function getTeaser()
    {
        return $this->teaser;
    }
    public function setTeaser($teaser)
    {
        $this->teaser = $teaser;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function addAsset($asset)
    {
        $this->assets[] = $asset;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get url
     *
     * @return string $body
     */
    public function getUrl()
    {
        return $this->url;
    }

    /*
     * Set data
     *
     * @param string $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /*
     * Get data
     *
     * @return string $body
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return \Bpi\ApiBundle\Domain\Entity\Resource\Body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return $this->assets;
    }

    public function getMaterials()
    {
        return $this->materials;
    }
}
