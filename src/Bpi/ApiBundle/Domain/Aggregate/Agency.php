<?php
namespace Bpi\ApiBundle\Domain\Aggregate;

use Bpi\ApiBundle\Transform\IPresentable;
use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Domain\ValueObject\Copyleft;
use Bpi\ApiBundle\Domain\ValueObject\AgencyId;

class Agency implements IPresentable, TitleWrapperInterface
{
    protected $id;
    protected $public_id;
    protected $name;
    protected $moderator;
    protected $internal;
    protected $public_key;
    protected $secret;
    protected $deleted = false;

    public function __construct($public_id = null, $name = null, $moderator = null, $internal = true, $public_key = null, $secret = null)
    {
        $this->public_id = $public_id;
        $this->name = $name;
        $this->moderator = $moderator;
        $this->internal = $internal;
        $this->setPublicKey($public_key);
        $this->setSecret($secret);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(Document $document)
    {
        $agencyInternal = (false === $this->internal) ? '0' : '1';
        $entity = $document->currentEntity();
        $entity->addProperty($document->createProperty('agency_id', 'string', $this->public_id));
        $entity->addProperty($document->createProperty('agency_name', 'string', $this->name));
        $entity->addProperty($document->createProperty('agency_internal', 'boolean', $agencyInternal));
    }

    /**
     * Set autorship
     *
     * @param \Bpi\ApiBundle\Domain\ValueObject\Copyleft $copyleft
     */
    public function setAuthorship(Copyleft $copyleft)
    {
        $copyleft->addCopyrigher($this->name);
    }

    /**
     * Get Agency ID
     *
     * @return \Bpi\ApiBundle\Domain\ValueObject\AgencyId
     */
    public function getAgencyId()
    {
        return new AgencyId($this->public_id);
    }

    /**
     * Check auth token.
     *
     * @return string
     */
    public function checkToken($token)
    {
        $localToken = crypt($this->public_id . $this->public_key . $this->secret, $token);
        return $token === $localToken;
    }

    public function __toString()
    {
        return (string) $this->public_id;
    }

    public function setDeleted($value = true)
    {
        $this->deleted = $value;
    }
    public function getDeleted()
    {
        return $this->deleted;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getPublicId()
    {
        return $this->public_id;
    }
    public function setPublicId($id)
    {
        $this->public_id = $id;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getModerator()
    {
        return $this->moderator;
    }
    public function setModerator($name)
    {
        $this->moderator = $name;
    }
    public function setPublicKey($key)
    {
        $this->public_key = empty($key) ? md5(microtime(true) . rand()) : $key;
    }
    public function getPublicKey()
    {
        return $this->public_key;
    }

    public function setSecret($secret)
    {
        $this->secret = empty($secret) ? sha1(microtime(true) . rand()) : $secret;
    }
    public function getSecret()
    {
        return $this->secret;
    }

    public function setInternal($internal)
    {
        $this->internal = $internal;
        return $this;
    }

    public function getInternal()
    {
        return $this->internal;
    }

}
