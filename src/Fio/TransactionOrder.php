<?php

namespace Lightools\Fio;

use InvalidArgumentException;
use Nette\Object;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * @author Jan Nedbal
 */
class TransactionOrder extends Object {

    const PAYMENT_TYPE_STANDARD = 431001;
    const PAYMENT_TYPE_FASTER = 431004;
    const PAYMENT_TYPE_PRIORITY = 431005;
    const PAYMENT_TYPE_ENCASHMENT = 431022;

    /**
     * @var int[]
     */
    public static $paymentTypes = [
        self::PAYMENT_TYPE_STANDARD,
        self::PAYMENT_TYPE_FASTER,
        self::PAYMENT_TYPE_PRIORITY,
        self::PAYMENT_TYPE_ENCASHMENT,
    ];

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $accountTo;

    /**
     * @var string
     */
    private $bankCode;

    /**
     * @var null|string
     */
    private $variableSymbol;

    /**
     * @var null|string
     */
    private $constantSymbol;

    /**
     * @var null|string
     */
    private $specificSymbol;

    /**
     * @var DateTime
     */
    private $maturityDate;

    /**
     * @var null|string
     */
    private $messageForRecipient;

    /**
     * @var int
     */
    private $paymentType;

    /**
     * @var null|string
     */
    private $comment;

    /**
     * @param float $amount Positive amount
     * @param string $currency Format ISO 4217
     * @param string $accountTo Format 123456-1234567890
     * @param string $bankCode Four digits expected
     */
    public function __construct($amount, $currency, $accountTo, $bankCode) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->accountTo = $accountTo;
        $this->bankCode = $bankCode;
        $this->paymentType = self::PAYMENT_TYPE_STANDARD;
        $this->maturityDate = new DateTime();

        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException('Amount must be numeric and positive.');

        } elseif (!Strings::match($accountTo, '~^([0-9]{2,6}-)?[0-9]{2,10}$~')) {
            throw new InvalidArgumentException('Invalid destination account, expected valid account number!');

        } elseif (!Strings::match($bankCode, '~^[0-9]{4}$~')) {
            throw new InvalidArgumentException('Invalid bank code, expected exactly four digits!');

        } elseif (!Strings::match($currency, '~^[A-Z]{3}$~')) {
            throw new InvalidArgumentException('Invalid currency, expected ISO 4217 format!');
        }
    }

    /**
     * @param string $variableSymbol
     */
    public function setVariableSymbol($variableSymbol) {
        if (!Strings::match($variableSymbol, '~^[0-9]{1,10}$~')) {
            throw new InvalidArgumentException('Invalid variable symbol, expected up to ten digits!');
        }

        $this->variableSymbol = $variableSymbol;
    }

    /**
     * @param string $constantSymbol
     */
    public function setConstantSymbol($constantSymbol) {
        if (!Strings::match($constantSymbol, '~^[0-9]{1,4}$~')) {
            throw new InvalidArgumentException('Invalid constant symbol, expected up to four digits!');
        }

        $this->constantSymbol = $constantSymbol;
    }

    /**
     * @param string $specificSymbol
     */
    public function setSpecificSymbol($specificSymbol) {
        if (!Strings::match($specificSymbol, '~^[0-9]{1,10}$~')) {
            throw new InvalidArgumentException('Invalid specific symbol, expected up to ten digits!');
        }

        $this->specificSymbol = $specificSymbol;
    }

    /**
     * Only day matters, time are ignored
     * @param DateTime $date
     */
    public function setMaturityDate(DateTime $date) {
        $this->maturityDate = $date;
    }

    /**
     * @param string $messageForRecipient Max 140 chars
     */
    public function setMessageForRecipient($messageForRecipient) {
        if (Strings::length($messageForRecipient) > 140) {
            throw new InvalidArgumentException('Invalid message for recipient, expected string shorter than 141 chars!');
        }

        $this->messageForRecipient = $messageForRecipient;
    }

    /**
     * @param int $paymentType
     */
    public function setPaymentType($paymentType) {
        if (!in_array($paymentType, self::$paymentTypes, TRUE)) {
            throw new InvalidArgumentException('Invalid payment type, expected one of PAYMENT_TYPE_* constants!');
        }
        $this->paymentType = $paymentType;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment) {
        if (Strings::length($comment) > 255) {
            throw new InvalidArgumentException('Invalid comment, expected string shorter than 256 chars!');
        }

        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getAccountTo() {
        return $this->accountTo;
    }

    /**
     * @return string
     */
    public function getBankCode() {
        return $this->bankCode;
    }

    /**
     * @return null|string
     */
    public function getVariableSymbol() {
        return $this->variableSymbol;
    }

    /**
     * @return null|string
     */
    public function getConstantSymbol() {
        return $this->constantSymbol;
    }

    /**
     * @return null|string
     */
    public function getSpecificSymbol() {
        return $this->specificSymbol;
    }

    /**
     * @return DateTime
     */
    public function getMaturityDate() {
        return $this->maturityDate;
    }

    /**
     * @return null|string
     */
    public function getMessageForRecipient() {
        return $this->messageForRecipient;
    }

    /**
     * self::PAYMENT_TYPE_*
     * @return int
     */
    public function getPaymentType() {
        return $this->paymentType;
    }

    /**
     * @return null|string
     */
    public function getComment() {
        return $this->comment;
    }

}
