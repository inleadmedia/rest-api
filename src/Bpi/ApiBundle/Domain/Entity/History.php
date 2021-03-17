<?php
namespace Bpi\ApiBundle\Domain\Entity;

use Bpi\ApiBundle\Domain\Aggregate\Node;

class History
{
    const ACTION_PUSH = 'push';

    const ACTION_SYNDICATE = 'syndicate';

    private $id;
    /**
     *
     * @var \Bpi\ApiBundle\Domain\Agregate\Node
     */
    private $node;
    private $agency;
    private $datetime;
    private $action;

    /**
     * Create node history entry.
     *
     * @param \Bpi\ApiBundle\Domain\Aggregate\Node $node
     * @param string $agency Public agency ID
     * @param \Datetime $datetime
     * @param string $action Possible values: push, syndicate
     */
    public function __construct(Node $node, $agency, \Datetime $datetime, $action)
    {
        $this->node = $node;
        $this->agency = $agency;
        $this->datetime = $datetime;
        $this->action = $action;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set node
     *
     * @param \Bpi\ApiBundle\Domain\Aggregate\Node $node
     * @return History
     */
    public function setNode($node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Get node
     *
     * @return string
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Set agency
     *
     * @param \Bpi\ApiBundle\Domain\Aggregate\Agency $agency
     * @return History
     */
    public function setAgency($agency)
    {
        $this->agency = $agency;

        return $this;
    }

    /**
     * Get agency
     *
     * @return \Bpi\ApiBundle\Domain\Aggregate\Agency
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * Set datetime
     *
     * @param \Datetime $time
     * @return History
     */
    public function setDatetime(\Datetime $time)
    {
        $this->datetime = $time->getTimestamp();

        return $this;
    }

    /**
     * Get datetime
     *
     * @return \Datetime
     */
    public function getDatetime()
    {
        return new \DateTime($this->datetime);
    }

    /**
     * Set action
     *
     * @param string $action
     * @return History
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

}
