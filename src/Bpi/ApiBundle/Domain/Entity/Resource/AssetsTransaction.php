<?php
namespace Bpi\ApiBundle\Domain\Entity\Resource;

use Bpi\ApiBundle\Domain\Entity\Asset;

/**
 * Deal with assets in transactional style
 */
class AssetsTransaction
{
    protected $fail = false;
    protected $storage;
    protected $exception;

    public function __construct()
    {
        $this->storage = new \SplObjectStorage();
    }

    /**
     * Add asset into transaction
     *
     * @param \Bpi\ApiBundle\Domain\Entity\Asset $asset
     */
    public function add(Asset $asset)
    {
        $this->storage->attach($asset);
    }

    /**
     * Mark transaction as failed
     *
     * @param \Exception $e
     */
    public function markAsFailed(\Exception $e)
    {
        $this->fail == true;
        $this->exception = $e;
    }

    /**
     * Throws the exception that failed current transaction
     *
     * @throws \Exception
     */
    public function throwTheReason()
    {
        throw $this->exception;
    }

    /**
     * Rollback transaction on fail, or do nothing
     *
     * @return bool true on rollback performed or false
     */
    public function rollbackOnFail()
    {
        if (!$this->fail)
            return false;

        foreach($this->storage as $asset)
            $asset->detach();

        return true;
    }
}
