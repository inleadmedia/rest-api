<?php
namespace Bpi\AdminBundle\Entity;

class Statistics
{

    private $dateFrom;

    private $dateTo;

    private $agency;

    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    public function getDateTo()
    {
        return $this->dateTo;
    }

    public function getAgency()
    {
        return $this->agency;
    }

    public function setDateFrom($date)
    {
        $this->dateFrom = new \DateTime();
        $this->dateFrom->setTimestamp(strtotime($date));
    }

    public function setDateTo($date)
    {
        $this->dateTo = new \DateTime();
        $this->dateTo->setTimestamp(strtotime($date));
    }

    public function setAgency($agency)
    {
        $this->agency = $agency;
    }
}
