<?php
namespace Bpi\ApiBundle\Transform\Extractor;

use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Domain\Aggregate\Params as DomainParams;
use Bpi\ApiBundle\Domain\ValueObject\Param\Authorship;
use Bpi\ApiBundle\Domain\ValueObject\Param\Editable;

/**
 * Extract NodeParams entity from presentation
 */
class Params implements IExtractor
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
     * @return Bpi\ApiBundle\Domain\Aggregate\Params
     */
    public function extract()
    {
        $entity = $this->doc->getEntity('params');
        $params = new DomainParams();
        $params->add(new Editable($entity->property('editable')->getValue()));
        $params->add(new Authorship($entity->property('authorship')->getValue()));

        return $params;
    }
}
