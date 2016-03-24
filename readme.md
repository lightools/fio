## Introduction

Library providing basic operations with Fio API.

## Installation

```sh
$ composer require lightools/fio
```

## Usage

This library doesn't implement all functions of Fio API (e.g. Euro or International payments),
it just provides simple interface for the most common use-cases.
You can easily work with multiple Fio accounts or you can use FioClient directly.

### Initialize

```php
$httpClient = new Bitbang\Http\Clients\CurlClient();
$xmlLoader = new Lightools\Xml\XmlLoader();

$fio = new Lightools\Fio\FioClient($xmlLoader, $httpClient);
$account = new Lightools\Fio\FioAccount('12345678', 'token', $fio); // no problem with having more Fio accounts
```

### Retrieving new payments

```php
try {
    $transactions = $account->getNewTransactions();
    foreach ($transactions as $transaction) {
        echo $transaction->getVariableSymbol();
    }

} catch (Lightools\Fio\FioException $e) { // or catch specific exceptions
    $account->setBreakpointById($lastKnownMoveId);
    // further processing
}
```

### Sending transaction orders

```php
try {
    $amount = 100;
    $currency = 'CZK';
    $accountTo = '12345678';
    $bankCode = '6100';
    $order = new Lightools\Fio\TransactionOrder($amount, $currency, $accountTo, $bankCode);
    $order->setVariableSymbol('8888');

    $account->sendOrders([$order]);

} catch (Lightools\Fio\FioTemporaryUnavailableException $e) {
    // Fio is overheated, wait 30 seconds and repeat

} catch (Lightools\Fio\FioWarningException $e) {
    // in this case, Fio accepted orders, but detected something suspicious

} catch (Lightools\Fio\FioFailureException $e) {
    // e.g. HTTP request failed, Fio is down, ...
}
```

## How to run tests

```sh
$ vendor/bin/tester -c tests/php.ini -d extension_dir=ext tests
```
