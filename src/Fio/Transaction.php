<?php

namespace Lightools\Fio;

use DateTime;
use InvalidArgumentException;
use Nette\Object;

/**
 * @author Jan Nedbal
 */
class Transaction extends Object {

    /**
     * 10 digit unique identifier
     * @var string
     */
    private $moveId;

    /**
     * @var string YYYY-MM-DD+GMT
     */
    private $moveDate;

    /**
     * @var int|float
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $account;

    /**
     * @var string
     */
    private $accountName;

    /**
     * @var string
     */
    private $bankCode;

    /**
     * @var string
     */
    private $bankName;

    /**
     * @var string
     */
    private $constantSymbol;

    /**
     * @var string
     */
    private $variableSymbol;

    /**
     * @var string
     */
    private $specificSymbol;

    /**
     * @var string
     */
    private $userIdentification;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $performed;

    /**
     * @var string
     */
    private $specification;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var string
     */
    private $bic;

    /**
     * @var string
     */
    private $instructionId;

    /**
     * @var array
     */
    private $mapping = [
        22 => 'moveId',
        0 => 'moveDate',
        1 => 'amount',
        14 => 'currency',
        2 => 'account',
        10 => 'accountName',
        3 => 'bankCode',
        12 => 'bankName',
        4 => 'constantSymbol',
        5 => 'variableSymbol',
        6 => 'specificSymbol',
        7 => 'userIdentification',
        16 => 'message',
        8 => 'type',
        9 => 'performed',
        18 => 'specification',
        25 => 'comment',
        26 => 'bic',
        17 => 'instructionId',
    ];

    /**
     * Set property by its FIO column id
     * @param int $id
     * @param mixed $value
     */
    public function setById($id, $value) {

        if (!isset($this->mapping[$id])) {
            throw new InvalidArgumentException("Unknown FIO transaction column id '$id'!");
        }

        $property = $this->mapping[$id];
        $this->$property = $value;
    }

    /**
     * @var string
     */
    public function getMoveId() {
        return $this->moveId;
    }

    /**
     * @var DateTime
     */
    public function getMoveDate() {
        return new DateTime($this->moveDate);
    }

    /**
     * @var float
     */
    public function getAmount() {
        return (float) $this->amount;
    }

    /**
     * @var string
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @var string
     */
    public function getAccount() {
        return $this->account;
    }

    /**
     * @var string
     */
    public function getAccountName() {
        return $this->accountName;
    }

    /**
     * @var string
     */
    public function getBankCode() {
        return $this->bankCode;
    }

    /**
     * @var string
     */
    public function getBankName() {
        return $this->bankName;
    }

    /**
     * @var string
     */
    public function getConstantSymbol() {
        return $this->constantSymbol;
    }

    /**
     * @var string
     */
    public function getVariableSymbol() {
        return $this->variableSymbol;
    }

    /**
     * @var string
     */
    public function getSpecificSymbol() {
        return $this->specificSymbol;
    }

    /**
     * @var string
     */
    public function getUserIdentification() {
        return $this->userIdentification;
    }

    /**
     * @var string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @var string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @var string
     */
    public function getPerformed() {
        return $this->performed;
    }

    /**
     * @var string
     */
    public function getSpecification() {
        return $this->specification;
    }

    /**
     * @var string
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * @var string
     */
    public function getBic() {
        return $this->bic;
    }

    /**
     * @var string
     */
    public function getInstructionId() {
        return $this->instructionId;
    }

}
