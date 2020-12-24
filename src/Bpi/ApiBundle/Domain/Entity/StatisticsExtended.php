<?php

namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Transform\IPresentable;
use Bpi\RestMediaTypeBundle\Document;
use Bpi\RestMediaTypeBundle\XmlResponse;

/**
 * Class StatisticsExtended.
 */
class StatisticsExtended implements IPresentable {

    protected $from;

    protected $to;

    protected $action;

    protected $aggregatedBy;

    protected $items;

    /**
     * StatisticsExtended constructor.
     *
     * @param \DateTime $from
     * @param \DateTime $to
     * @param string $action
     * @param string $aggregatedBy
     * @param array $items
     */
    public function __construct(\DateTime $from, \DateTime $to, $action, $aggregatedBy, $items = [])
    {
        $this->from = $from;
        $this->to = $to;
        $this->action = $action;
        $this->aggregatedBy = $aggregatedBy;
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Bpi\RestMediaTypeBundle\Document $document
     */
    public function transform(Document $document)
    {
        $entity = $document->createEntity('statistic', 'meta');
        $entity->addProperty(
            $document->createProperty('from', 'date', $this->from->format(DATE_ISO8601))
        );
        $entity->addProperty(
            $document->createProperty('to', 'date', $this->to->format(DATE_ISO8601))
        );
        $document->appendEntity($entity);

        $entity = $document->createEntity('statistic', 'top');
        $document->appendEntity($entity);

        foreach ($this->items as $item) {
            $entity->addProperty(
                $document->createProperty($item['id'], 'string', $item['total'], $item['title'])
            );
        }

        return $document;
    }
}
