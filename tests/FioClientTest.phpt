<?php

namespace Tests;

use Bitbang\Http\Clients\CurlClient;
use Bitbang\Http\Request;
use Bitbang\Http\Response;
use CURLFile;
use DateTime;
use Lightools\Fio\FioClient;
use Lightools\Fio\FioFailureException;
use Lightools\Fio\FioTemporaryUnavailableException;
use Lightools\Fio\FioWarningException;
use Lightools\Fio\TransactionOrder;
use Lightools\Xml\XmlLoader;
use Mockery;
use Mockery\MockInterface;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../vendor/autoload.php';

Environment::setup();

/**
 * @testCase
 * @author Jan Nedbal
 */
class FioClientTest extends TestCase {

    protected function setUp() {
        parent::setUp();
        Mockery::getConfiguration()->allowMockingNonExistentMethods(FALSE);
    }

    public function testNewTransactions() {
        $data = file_get_contents(__DIR__ . '/responses/transactions.json');

        $httpClient = $this->getHttpClientMock($data);
        $fioClient = new FioClient(new XmlLoader(), $httpClient);

        $transactions = $fioClient->getNewTransactions('token');

        Assert::type('array', $transactions);
        Assert::count(2, $transactions);
        Assert::same(-5000.0, $transactions[0]->getAmount());
        Assert::type(DateTime::class, $transactions[0]->getMoveDate());
        Assert::same('Comment', $transactions[0]->getComment());
    }

    public function testSendOrders() {
        $data = file_get_contents(__DIR__ . '/responses/ordered.xml');
        $xmlLoader = new XmlLoader();

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getCode')->once()->andReturn(Response::S200_OK);
        $response->shouldReceive('getBody')->once()->andReturn($data);

        $requestCheckCallback = function (Request $request) use ($xmlLoader) {
            $method = $request->getMethod();
            $body = $request->getBody();

            Assert::same(Request::POST, $method);
            Assert::same('token', $body['token']);
            Assert::same('xml', $body['type']);
            Assert::same('cs', $body['lng']);
            Assert::type(CURLFile::class, $body['file']);

            $xmlString = file_get_contents($body['file']->getFilename());
            $xml = $xmlLoader->loadXml($xmlString);

            Assert::same(TransactionOrder::PAYMENT_TYPE_STANDARD, (int) $xml->getElementsByTagName('paymentType')->item(0)->nodeValue);
            Assert::same('1000.30', $xml->getElementsByTagName('amount')->item(0)->nodeValue);
            Assert::same('CZK', $xml->getElementsByTagName('currency')->item(0)->nodeValue);
            Assert::same('12345678', $xml->getElementsByTagName('accountTo')->item(0)->nodeValue);
            Assert::same('6100', $xml->getElementsByTagName('bankCode')->item(0)->nodeValue);
            Assert::same('8888', $xml->getElementsByTagName('vs')->item(0)->nodeValue);
            Assert::same('foo', $xml->getElementsByTagName('comment')->item(0)->nodeValue);
            Assert::same(date('Y-m-d'), $xml->getElementsByTagName('date')->item(0)->nodeValue);
            Assert::same('', $xml->getElementsByTagName('ss')->item(0)->nodeValue);
            Assert::same('', $xml->getElementsByTagName('ks')->item(0)->nodeValue);
            Assert::same('', $xml->getElementsByTagName('messageForRecipient')->item(0)->nodeValue);

            return TRUE;
        };

        $httpClient = Mockery::mock(CurlClient::class);
        $httpClient->shouldReceive('process')->with(Mockery::on($requestCheckCallback))->once()->andReturn($response);

        $fioClient = new FioClient($xmlLoader, $httpClient);

        $amount = 1000.3;
        $currency = 'CZK';
        $accountTo = '12345678';
        $bankCode = '6100';
        $order = new TransactionOrder($amount, $currency, $accountTo, $bankCode);
        $order->setVariableSymbol('8888');
        $order->setComment('foo');

        Assert::noError(function () use ($fioClient, $order) {
            $fioClient->sendTransactionOrders('token', '11112222', [$order]);
        });
    }

    public function testWarning() {
        $data = file_get_contents(__DIR__ . '/responses/ordered-warning.xml');
        $httpClient = $this->getHttpClientMock($data);
        $fioClient = new FioClient(new XmlLoader(), $httpClient);

        $order = Mockery::mock(TransactionOrder::class);
        $order->shouldReceive('getMaturityDate')->andReturn(new DateTime());
        $order->shouldIgnoreMissing();

        Assert::exception(function () use ($fioClient, $order) {
            $fioClient->sendTransactionOrders('token', '11112222', [$order]);
        }, FioWarningException::class);
    }

    public function testTemporaryUnavailable() {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getCode')->once()->andReturn(FioClient::CODE_UNAVAILABLE);

        $httpClient = Mockery::mock(CurlClient::class);
        $httpClient->shouldReceive('process')->with(Request::class)->once()->andReturn($response);

        $fioClient = new FioClient(new XmlLoader(), $httpClient);

        Assert::exception(function () use ($fioClient) {
            $fioClient->getNewTransactions('token');
        }, FioTemporaryUnavailableException::class);
    }

    public function testInvalidJson() {
        $httpClient = $this->getHttpClientMock('Service unavailable');
        $fioClient = new FioClient(new XmlLoader(), $httpClient);

        Assert::exception(function () use ($fioClient) {
            $fioClient->getNewTransactions('token');
        }, FioFailureException::class);
    }

    public function testInvalidHttpCode() {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getCode')->once()->andReturn(Response::S400_BAD_REQUEST);

        $httpClient = Mockery::mock(CurlClient::class);
        $httpClient->shouldReceive('process')->with(Request::class)->once()->andReturn($response);

        $fioClient = new FioClient(new XmlLoader(), $httpClient);

        Assert::exception(function () use ($fioClient) {
            $fioClient->getNewTransactions('token');
        }, FioFailureException::class);
    }

    protected function tearDown() {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @param string $responseBody
     * @return MockInterface
     */
    private function getHttpClientMock($responseBody) {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getCode')->once()->andReturn(Response::S200_OK);
        $response->shouldReceive('getBody')->once()->andReturn($responseBody);

        $httpClient = Mockery::mock(CurlClient::class);
        $httpClient->shouldReceive('process')->with(Request::class)->once()->andReturn($response);
        return $httpClient;
    }

}

(new FioClientTest)->run();
