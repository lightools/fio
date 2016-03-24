<?php

namespace Lightools\Fio;

use Nette\Object;

/**
 * @author Jan Nedbal
 */
class FioAccount extends Object {

    /**
     * @var string
     */
    private $bankNumber;

    /**
     * @var string
     */
    private $token;

    /**
     * @var Manager
     */
    private $fio;

    /**
     * @param string $bankNumber
     * @param string $token
     * @param FioClient $fio
     */
    public function __construct($bankNumber, $token, FioClient $fio) {
        $this->bankNumber = $bankNumber;
        $this->token = $token;
        $this->fio = $fio;
    }

    /**
     * @param TransactionOrder[] $orders
     * @throws FioFailureException
     * @throws FioWarningException
     * @throws FioTemporaryUnavailableException
     */
    public function sendOrders(array $orders) {
        $this->fio->sendTransactionOrders($this->token, $this->bankNumber, $orders);
    }

    /**
     * Reset breakpoint to last known moveId
     * @param string $moveId
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function setBreakpointById($moveId) {
        $this->fio->setBreakpointById($this->token, $moveId);
    }

    /**
     * @param DateTime $date
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function setBreakpointByDate(DateTime $date) {
        $this->fio->setBreakpointByDate($this->token, $date);
    }

    /**
     * Get new transactions since since last retrieval
     * @return Transaction[]
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function getNewTransactions() {
        return $this->fio->getNewTransactions($this->token);
    }

    /**
     * Get new transactions since since last retrieval
     * @param DateTime $from
     * @param DateTime $to
     * @return Transaction[]
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function getTransactions(DateTime $from, DateTime $to) {
        return $this->fio->getTransactions($this->token, $from, $to);
    }

}
