<?php

namespace Lightools\Fio;

use Bitbang\Http\BadResponseException;
use Bitbang\Http\Clients\CurlClient;
use Bitbang\Http\Request;
use Bitbang\Http\Response;
use CURLFile;
use DateTime;
use Lightools\Xml\XmlException;
use Lightools\Xml\XmlLoader;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use stdClass;
use XMLWriter;

/**
 * @author Jan Nedbal
 */
class FioClient extends Object {

    /**
     * HTTP Conflict code (happens mainly when requests are sent in interval shorter than 30 sec)
     * @var int
     */
    const CODE_UNAVAILABLE = 409;

    /**
     * @var string
     */
    const STATUS_OK = 'ok';

    /**
     * @var string
     */
    const STATUS_WARNING = 'warning';

    /**
     * Url for requesting transaction list
     * @var string
     */
    const DOWNLOAD_URL = 'https://www.fio.cz/ib_api/rest/';

    /**
     * Url for sending transaction orders
     * @var string
     */
    const UPLOAD_URL = 'https://www.fio.cz/ib_api/rest/import/';

    /**
     * @var CurlClient
     */
    private $httpClient;

    /**
     * @var XmlLoader
     */
    private $xmlLoader;

    public function __construct(XmlLoader $xmlLoader, CurlClient $client) {
        $this->xmlLoader = $xmlLoader;
        $this->httpClient = $client;
    }

    /**
     * Get transactions sice last download
     *
     * @param string $token
     * @return Transaction[]
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function getNewTransactions($token) {
        $url = self::DOWNLOAD_URL . "last/$token/transactions.json";

        $response = $this->download($url);
        return $this->getTransactionsFromResponse($response);
    }

    /**
     * @param string $token
     * @param DateTime $from
     * @param DateTime $to
     * @return Transaction[]
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function getTransactions($token, DateTime $from, DateTime $to) {
        $fromDate = $from->format('Y-m-d');
        $toDate = $to->format('Y-m-d');
        $url = self::DOWNLOAD_URL . "periods/$token/$fromDate/$toDate/transactions.json";

        $response = $this->download($url);
        return $this->getTransactionsFromResponse($response);
    }

    /**
     * Set brakepoint to moveId given
     * @param string $token
     * @param string $moveId
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function setBreakpointById($token, $moveId) {
        $url = self::DOWNLOAD_URL . "set-last-id/$token/$moveId/";
        $this->download($url);
    }

    /**
     * Set brakepoint to moveId given
     * @param string $token
     * @param DateTime $date
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    public function setBreakpointByDate($token, DateTime $date) {
        $dateString = $date->format('Y-m-d');
        $url = self::DOWNLOAD_URL . "set-last-date/$token/$dateString/";
        $this->download($url);
    }

    /**
     * Export (send) domestic transactions
     * @param string $token
     * @param string $accountFrom
     * @param TransactionOrder[] $orders
     * @throws FioFailureException
     * @throws FioWarningException
     * @throws FioTemporaryUnavailableException
     */
    public function sendTransactionOrders($token, $accountFrom, array $orders) {
        $xml = $this->createUploadXml($accountFrom, $orders);
        $this->upload($token, $xml);
    }

    /**
     * @param string $accountFrom
     * @param TransactionOrder[] $orders
     * @return string
     */
    private function createUploadXml($accountFrom, array $orders) {

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('Import');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xsi:noNamespaceSchemaLocation', 'http://www.fio.cz/schema/importIB.xsd');
        $xml->startElement('Orders');

        foreach ($orders as $order) {
            $xml->startElement('DomesticTransaction');
            $xml->writeElement('accountFrom', $accountFrom);
            $xml->writeElement('currency', $order->getCurrency());
            $xml->writeElement('amount', number_format($order->getAmount(), 2, '.', ''));
            $xml->writeElement('accountTo', $order->getAccountTo());
            $xml->writeElement('bankCode', $order->getBankCode());
            $xml->writeElement('ks', $order->getConstantSymbol());
            $xml->writeElement('vs', $order->getVariableSymbol());
            $xml->writeElement('ss', $order->getSpecificSymbol());
            $xml->writeElement('date', $order->getMaturityDate()->format('Y-m-d'));
            $xml->writeElement('messageForRecipient', $order->getMessageForRecipient());
            $xml->writeElement('comment', $order->getComment());
            $xml->writeElement('paymentType', $order->getPaymentType());
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * @param string $token
     * @param string $xml
     * @throws FioFailureException
     * @throws FioWarningException
     * @throws FioTemporaryUnavailableException
     */
    private function upload($token, $xml) {

        $filepath = tempnam(sys_get_temp_dir(), 'fio');
        FileSystem::write($filepath, $xml);

        $post = [
            'token' => $token,
            'type' => 'xml',
            'lng' => 'cs',
            'file' => new CURLFile($filepath),
        ];
        $request = new Request(Request::POST, self::UPLOAD_URL, [], $post);
        $response = $this->sendRequest($request);

        $this->checkUploadResponse($response->getBody());
    }

    /**
     * @param string $url
     * @return Response
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    private function download($url) {
        $request = new Request(Request::GET, $url);
        $response = $this->sendRequest($request);
        return $response;
    }

    /**
     * @param Response $response
     * @return Transaction[]
     * @throws FioFailureException
     */
    private function getTransactionsFromResponse(Response $response) {
        try {
            $json = Json::decode($response->getBody());

            if (!$json->accountStatement->transactionList) {
                return [];
            }

            $transactions = [];
            foreach ($json->accountStatement->transactionList->transaction as $row) {
                $transactions[] = $this->createTransaction($row);
            }

            return $transactions;

        } catch (JsonException $e) {
            throw new FioFailureException('Invalid JSON from FIO API', NULL, $e);
        }
    }

    /**
     * @param stdClass $row
     * @return Transaction
     */
    private function createTransaction(stdClass $row) {
        $transaction = new Transaction();
        foreach ($row as $column) {
            if ($column) {
                $transaction->setById($column->id, $column->value);
            }
        }
        return $transaction;
    }

    /**
     * @param string $response XML response
     * @throws FioFailureException
     * @throws FioWarningException
     */
    private function checkUploadResponse($response) {

        try {
            $xml = $this->xmlLoader->loadXml($response);

        } catch (XmlException $e) {
            throw new FioFailureException('Invalid XML received from Fio API!', NULL, $e);
        }

        $status = $xml->getElementsByTagName('status')->item(0)->nodeValue;

        if ($status === self::STATUS_WARNING) {
            throw new FioWarningException('Sending Fio orders succeded with warning!');

        } elseif ($status !== self::STATUS_OK) {
            throw new FioFailureException('Sending Fio orders failed!');
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws FioFailureException
     * @throws FioTemporaryUnavailableException
     */
    private function sendRequest(Request $request) {
        try {
            $response = $this->httpClient->process($request);
            $httpCode = $response->getCode();

            if ($httpCode === self::CODE_UNAVAILABLE) {
                throw new FioTemporaryUnavailableException('Fio overheated. Please wait 30 seconds.');

            } elseif ($httpCode !== Response::S200_OK) {
                throw new FioFailureException("Unexpected HTTP code $httpCode from Fio API.");
            }

            return $response;

        } catch (BadResponseException $e) {
            throw new FioFailureException('HTTP request to Fio API failed.', NULL, $e);
        }
    }

}
