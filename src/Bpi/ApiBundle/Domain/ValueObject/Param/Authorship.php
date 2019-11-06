<?php
namespace Bpi\ApiBundle\Domain\ValueObject\Param;

class Authorship implements IParam
{
    protected $authorship = false;

    public function __construct($authorship)
    {
        $this->authorship = (bool)$authorship;
    }

    public function isPositive()
    {
      return $this->authorship;
    }
}
