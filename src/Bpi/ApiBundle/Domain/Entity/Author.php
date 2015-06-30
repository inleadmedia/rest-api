<?php
namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Domain\ValueObject\AgencyId;
use Bpi\ApiBundle\Domain\ValueObject\Copyleft;
use Bpi\RestMediaTypeBundle\Document;

class Author implements \Bpi\ApiBundle\Transform\IPresentable
{
    /**
     *
     * @var Bpi\ApiBundle\Domain\ValueObject\AgencyId
     */
    protected $agency_id;

    protected $agency;

    /**
     *
     * @var string
     */
    protected $client_id;

    /**
     *
     * @var string
     */
    protected $firstname;

    /**
     *
     * @var string|null
     */
    protected $lastname;

    /**
     *
     * @param \Bpi\ApiBundle\Domain\ValueObject\AgencyId $agency_id
     * @param string $client_id Author ID in client local system
     * @param string $lastname
     * @param string|null $firstname
     */
    public function __construct(AgencyId $agency_id, $client_id, $lastname, $firstname = null)
    {
        $this->agency_id = $agency_id;
        $this->client_id = $client_id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }

    /**
     *
     * @return \Bpi\ApiBundle\Domain\ValueObject\AgencyId $agency_id
     */
    public function getAgencyId()
    {
        return is_object($this->agency_id) ? $this->agency_id : new AgencyId($this->agency_id);
    }

    /**
     *
     * @return string
     */
    public function getFullName()
    {
        return ($this->firstname ? $this->firstname.' ' : '') . $this->lastname;
    }

    /**
     * Set autorship
     *
     * @param \Bpi\ApiBundle\Domain\ValueObject\Copyleft $copyleft
     */
    public function setAuthorship(Copyleft $copyleft)
    {
        $copyleft->addCopyrigher($this->getFullName(), false);
    }

    /**
     * {@inheritdoc}
     */
    public function transform(Document $document)
    {
        try {
            $entity = $document->currentEntity();
        } catch(\RuntimeException $e) {
            $entity = $document->createEntity('entity', 'author');
            $document->appendEntity($entity);
        }

        $entity->addProperty($document->createProperty(
            'author',
            'string',
            $this->getFullName()
        ));

        if ($this->agency instanceof \Bpi\ApiBundle\Domain\Aggregate\Agency) {
            $this->agency->transform($document);
        }
    }

    public function loadAgency(\Bpi\ApiBundle\Domain\Repository\AgencyRepository $repository) {
        $this->agency = $repository->findOneBy(array('public_id' => $this->agency_id));
    }

    /**
     * Set agencyId
     *
     * @param string $agencyId
     * @return self
     */
    public function setAgencyId($agencyId)
    {
        $this->agency_id = $agencyId;
        return $this;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return self
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Get firstname
     *
     * @return string $firstname
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return self
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Get lastname
     *
     * @return string $lastname
     */
    public function getLastname()
    {
        return $this->lastname;
    }
}
