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
}
