<?php
namespace Bpi\ApiBundle\Domain\ValueObject\Param;

class Editable implements IParam
{
    protected $editable = false;

    public function __construct($editable)
    {
        $this->editable = (bool)$editable;
    }

    public function isPositive()
    {
        return $this->editable;
    }

    /**
     * Set editable
     *
     * @param string $editable
     * @return self
     */
    public function setEditable($editable)
    {
        $this->editable = $editable;
        return $this;
    }

    /**
     * Get editable
     *
     * @return string $editable
     */
    public function getEditable()
    {
        return $this->editable;
    }
}
