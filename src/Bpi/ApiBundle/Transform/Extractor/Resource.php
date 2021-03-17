<?php
namespace Bpi\ApiBundle\Transform\Extractor;

use Bpi\RestMediaTypeBundle\Document;
use Bpi\RestMediaTypeBundle\Element\Property;
use Bpi\ApiBundle\Domain\Factory\ResourceBuilder;
use Gaufrette\File;
use Gaufrette\Adapter\InMemory as MemoryAdapter;
use Gaufrette\Filesystem;

/**
 * Extract Resource entity from presentation
 */
class Resource implements IExtractor
{
    /**
     * @var Document
     */
    protected $doc;

    /**
     *
     * {@inheritdoc}
     */
    public function __construct(Document $doc)
    {
        $this->doc = $doc;
    }

    /**
     * {@inheritdoc}
     * @return Bpi\ApiBundle\Domain\Factory\ResourceBuilder
     */
    public function extract()
    {
        $entity = $this->doc->getEntity('resource');
        $builder = new ResourceBuilder();
        $fs = new Filesystem(new MemoryAdapter());

        $entity->walk(function($e) use ($builder, $fs) {
            if ($e->typeOf(Property::TYPE_ASSET)) {
                $file = new File($e->getName(), $fs);
                $file->setContent($e->getValue());
                $builder->addFile($file);
            }
        });

        return $builder
            ->title($entity->property('title')->getValue())
            ->body($entity->property('body')->getValue())
            ->teaser($entity->property('teaser')->getValue())
            ->url($entity->property('url')->getValue())
            ->data($entity->property('data')->getValue())
            ->ctime(new \DateTime($entity->property('ctime')->getValue()))
        ;
    }
}
