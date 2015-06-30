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

    /**
     * Set authorship
     *
     * @param string $authorship
     * @return self
     */
    public function setAuthorship($authorship)
    {
        $this->authorship = $authorship;
        return $this;
    }

    /**
     * Get authorship
     *
     * @return string $authorship
     */
    public function getAuthorship()
    {
        return $this->authorship;
    }
}
