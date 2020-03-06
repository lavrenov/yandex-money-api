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
        $this->yandex = new API($this->token);
    }

    public function testAccountInfo()
    {
        $result = $this->yandex->accountInfo();
        $this->assertEquals($this->account, $result->account);
    }

    public function testOperationHistory()
    {
        $result = $this->yandex->operationHistory();
        $this->assertObjectHasAttribute('operations', $result);
    }

    public function testOperationDetails()
    {
        $result = $this->yandex->operationHistory();
        $result = $this->yandex->operationDetails($result->operations[0]->operation_id);
        $this->assertObjectHasAttribute('operation_id', $result);
    }

    public function testRequestPaymentPhone()
    {
        $result = $this->yandex->requestPaymentPhone('79179249957', 10.0);
        $this->assertObjectHasAttribute('request_id', $result);
    }
}
