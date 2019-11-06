<?php
namespace Bpi\ApiBundle\Domain\ValueObject;

use Bpi\ApiBundle\Domain\Repository\YearwheelRepository;
use Bpi\ApiBundle\Transform\Comparator;
use Bpi\ApiBundle\Transform\IPresentable;

class Yearwheel implements IValueObject, IPresentable
{
    /**
     *
     * @var string
     */
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of yearwheel
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     *
     * @param \Bpi\ApiBundle\Domain\ValueObject\Yearwheel $yearwheel
     * @param string $field
     * @param int $order 1=asc, -1=desc
     * @return int see strcmp PHP function
     */
    public function compare(Yearwheel $yearwheel, $field, $order = 1)
    {
        $cmp = new Comparator($this->$field, $yearwheel->$field, $order);
        return $cmp->getResult();
    }

    /**
     * @param \Bpi\ApiBundle\Domain\ValueObject\Yearwheel $yearwheel
     * @return boolean
     */
    public function equals(IValueObject $yearwheel)
    {
        if (get_class($this) != get_class($yearwheel))
            return false;

        return $this->name() == $yearwheel->name();
    }

    /**
     * @param \Bpi\ApiBundle\Domain\Repository\YearwheelRepository $repository
     * @return boolean
     */
    public function isInRepository(YearwheelRepository $repository)
    {
        return $repository->findAll()->contains($this);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Bpi\RestMediaTypeBundle\Document $document
     */
    public function transform(\Bpi\RestMediaTypeBundle\Document $document)
    {
        $document->currentEntity()->addProperty($document->createProperty($this->name, 'yearwheel', $this->name));
    }
}
