<?php
namespace Bpi\ApiBundle\Domain\ValueObject;

class Copyleft implements IValueObject
{
    protected $copyrighters = array();

    /**
     * Name of copyleft owner
     *
     * @param string $name
     * @param boolean $append true for add item to the end, otherwise prepend
     */
    public function addCopyrigher($name, $append = true)
    {
        $append ? array_push($this->copyrighters, $name) : array_unshift($this->copyrighters, $name);
    }

    /**
     *
     * @param  \Bpi\ApiBundle\Domain\ValueObject\Copyleft $copyleft
     * @return boolean
     */
    public function equals(IValueObject $copyleft)
    {
        return $this->copyrighters == $copyleft->copyrighters;
    }

    public function __toString()
    {
        //TODO: Temporary hardcoded translation.
        return 'Oprindeligt skrevet af ' . implode(', ', $this->copyrighters) . '.';
    }
}
