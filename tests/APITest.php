<?php

use PHPUnit\Framework\TestCase;
use YandexMoney\API;

class APITest extends TestCase
{
    protected $yandex;
    protected $account = '';
    protected $clientId = '';
    protected $token = '';


    protected function setUp(): void
    {
        $this->account = $_ENV['YANDEX_ACCOUNT'];
        $this->clientId = $_ENV['YANDEX_CLIENT_ID'];
        $this->token = $_ENV['YANDEX_TOKEN'];
        $this->yandex = new API($this->token);
    }

    public function testAccountInfo()
    {
        $result = $this->yandex->accountInfo();
        $this->assertEquals('Token is empty', $result->error);
    }

    public function testOperationHistory()
    {
        $result = $this->yandex->operationHistory();
        $this->assertEquals('Token is empty', $result->error);
    }

    public function testOperationDetails()
    {
        $result = $this->yandex->operationHistory();
        if (!property_exists($result, 'error'))
            $result = $this->yandex->operationDetails($result->operations[0]->operation_id);
        $this->assertEquals('Token is empty', $result->error);
    }

    public function testRequestPaymentPhone()
    {
        $result = $this->yandex->requestPaymentPhone('79179249957', 10.0);
        $this->assertEquals('Token is empty', $result->error);
    }
}
