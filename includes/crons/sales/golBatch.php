<?php

use Guzzle\Batch\Batch;
use Guzzle\Batch\BatchTransferInterface;
use Guzzle\Batch\BatchDivisorInterface;
use Guzzle\Batch\BatchInterface;
use Guzzle\Batch\Exception\BatchTransferException;
/**
* GOL
* (C) 2014 GOL, Levi Voorintholt <levi@gamingonlinux.com> 
*/
class golBatch extends Batch implements BatchInterface
{
	
	public function __construct(BatchTransferInterface $transferStrategy, BatchDivisorInterface $divisionStrategy, $batchWait=NULL)
	{
		$this->waitTime = $batchWait;
		parent::__construct($transferStrategy, $divisionStrategy);
	}

    public function flush($callable=NULL)
    {
        $this->createBatches();
        $items = array();
        foreach ($this->dividedBatches as $batchIndex => $dividedBatch) {
            while ($dividedBatch->valid()) {
                $batch = $dividedBatch->current();
                $dividedBatch->next();
                try {
                    $this->transferStrategy->transfer($batch);

                    if (is_callable($callable)){
                    	$r = call_user_func_array($callable, array($batch, $this->transferStrategy));
                    	if ( is_array($r) ) $batch = $r;
                    }

                    $items = array_merge($items, $batch);

                    if ($this->waitTime != null) sleep($this->waitTime);
                } catch (\Exception $e) {
                    throw new BatchTransferException($batch, $items, $e, $this->transferStrategy, $this->divisionStrategy);
                }
            }
            // Keep the divided batch down to a minimum in case of a later exception
            unset($this->dividedBatches[$batchIndex]);
        }
        return $items;
    }
}