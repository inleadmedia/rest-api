<?php
namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Transform\IPresentable;

class Audience implements IPresentable
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $audience;

    /**
     * @var boolean
     */
    protected $disabled;

    /**
     * @param string $audience
     */
    public function __construct($audience = null)
    {
        $this->setDisabled(false);
        $this->setAudience($audience);
    }

    /**
     * Set audience.
     *
     * @param string $audience
     */
    public function setAudience($audience)
    {
        $this->audience = $audience;
    }

    /**
     * Get audience name.
     *
     * @return string
     */
    public function getAudience()
    {
        return $this->audience;
    }

    /**
     * Get audience ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets disabled value.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Sets disabled value.
     *
     * @param boolean $disabled Parameter value.
     *
     * @return self
     */
    public function setDisabled($disabled)
    {
        $this->disabled = (boolean) $disabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Bpi\RestMediaTypeBundle\Document $document
     */
    public function transform(\Bpi\RestMediaTypeBundle\Document $document)
    {
        $entity = $document->createEntity('audience');
        $document->appendEntity($entity);

        $entity->addProperty($document->createProperty('group', 'string', 'audience'));
        $entity->addProperty($document->createProperty('name', 'string', $this->getAudience()));
    }
}
