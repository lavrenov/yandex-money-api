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
        $this->account = getenv('YANDEX_ACCOUNT') ?? '';
        $this->clientId = getenv('YANDEX_CLIENT_ID') ?? '';
        $this->token = getenv('YANDEX_TOKEN') ?? '';
        $this->yandex = new API($this->token);
    }

    public function testAccountInfo()
    {
        $result = $this->yandex->accountInfo();
        if (property_exists($result, 'error'))
            $this->fail($result->error);
        $this->assertEquals($this->account, $result->account);
    }

    public function testOperationHistory()
    {
        $result = $this->yandex->operationHistory();
        if (property_exists($result, 'error'))
            $this->fail($result->error);
        $this->assertObjectHasAttribute('operations', $result);
    }

    public function testOperationDetails()
    {
        $result = $this->yandex->operationHistory();
        if (property_exists($result, 'error'))
            $this->fail($result->error);
        $result = $this->yandex->operationDetails($result->operations[0]->operation_id);
        if (property_exists($result, 'error'))
            $this->fail($result->error);
        $this->assertObjectHasAttribute('operation_id', $result);
    }

    public function testRequestPaymentPhone()
    {
        $result = $this->yandex->requestPaymentPhone('79179249957', 10.0);
        if (property_exists($result, 'error'))
            $this->fail($result->error);
        $this->assertObjectHasAttribute('request_id', $result);
    }
}
