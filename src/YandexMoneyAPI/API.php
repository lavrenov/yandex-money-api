<?php

namespace YandexMoney;

class API {
	private static $domain = 'https://money.yandex.ru';
	private $token = null;
	public static $responseSuccess = false;

	const SC_ACCOUNT_INFO = 'account-info';
	const SC_OPERATION_HISTORY = 'operation-history';
	const SC_OPERATION_DETAILS = 'operation-details';
	const SC_INCOMING_TRANSFERS = 'incoming-transfers';
	const SC_PAYMENT_SHOP = 'payment-shop';
	const SC_PAYMENT_P2P = 'payment-p2p';

	const SC_PAYMENT = 'payment';
	const SC_MONEY_SOURCE = 'money-source';

	const SC_INFO = [
		self::SC_ACCOUNT_INFO,
		self::SC_OPERATION_HISTORY,
		self::SC_OPERATION_DETAILS,
	];

	const SC_TRANSACTIONS = [
		self::SC_INCOMING_TRANSFERS,
		self::SC_PAYMENT_SHOP,
		self::SC_PAYMENT_P2P,
	];

	const SC_ALL = [
		self::SC_ACCOUNT_INFO,
		self::SC_OPERATION_HISTORY,
		self::SC_OPERATION_DETAILS,
		self::SC_INCOMING_TRANSFERS,
		self::SC_PAYMENT_SHOP,
		self::SC_PAYMENT_P2P,
	];

	const OP_DEPOSITION = 'deposition';
	const OP_PAYMENT = 'payment';
	const OP_INCOMING_TRANSFERS_UNACCEPTED = 'incoming-transfers-unaccepted';
	const OP_PAYMENT_SHOP = 'payment-shop';
	const OP_OUTGOING_TRANSFER = 'outgoing-transfer';
	const OP_INCOMING_TRANSFER = 'incoming-transfer';
	const OP_INCOMING_TRANSFER_PROTECTED = 'incoming-transfer-protected';

	function __construct(string $token)
	{
		$this->token = $token;
	}

	function sendAuthRequest(string $url, array $options = [])
	{
		if ($this->token == null)
			return ['error' => 'Token is empty'];
		return self::sendRequest($url, $options, $this->token);
	}

	function accountInfo()
	{
		return self::sendAuthRequest('/api/account-info');
	}

	function operationHistory(string $from = '', string $till = '', string $type = '', int $startRecord = 0, int $records = 30)
	{
		if ($from)
			$from = date("c", strtotime($from));
		if ($till)
			$till = date("c", strtotime($till));
		$options = [
			'from' => $from,
			'till' => $till,
			'type' => $type,
			'start_record' => $startRecord,
			'records' => $records,
			//'label' => $label,
			//'details' => $details,
		];
		$options = array_filter($options);
		return $this->sendAuthRequest('/api/operation-history', $options);
	}

	function operationDetails(string $operationId)
	{
		return $this->sendAuthRequest('/api/operation-details', [
			'operation_id' => $operationId
		]);
	}

	function requestPaymentShop(string $patternId, array $params)
	{
		$options = [
			'pattern_id' => $patternId,
		];
		$options = array_merge($options, $params);
		return $this->sendAuthRequest('/api/request-payment', $options);
	}

	function requestPaymentP2P(string $to, float $amount_due, string $message = '', string $comment = '',
	                           bool $codePro = false, bool $hold_for_pickup = false, int $expire_period = 1)
	{
		$options = [
			'pattern_id' => 'p2p',
			'to' => $to,
			'amount_due' => $amount_due,
			'comment' => $comment,
			'message' => $message,
			'codepro' => $codePro,
			'hold_for_pickup' => $hold_for_pickup,
			'expire_period' => $expire_period,
			//'amount' => $amount,
			//'label' => $label,
		];
		$options = array_filter($options);
		return $this->sendAuthRequest('/api/request-payment', $options);
	}

	function requestPaymentPhone(string $phoneNumber, float $amount)
	{
		$options = [
			'pattern_id' => 'phone-topup',
			'phone-number' => $phoneNumber,
			'amount' => $amount,
		];
		$options = array_filter($options);
		return $this->sendAuthRequest('/api/request-payment', $options);
	}

	function processPayment(string $requestId, string $moneySource = 'wallet', string $csc = '',
	                        string $extAuthSuccessUri = '', string $extAuthFailUri = '')
	{
		$options = [
			'request_id' => $requestId,
			'money_source' => $moneySource,
			'csc' => $csc,
			'ext_auth_success_uri' => $extAuthSuccessUri,
			'ext_auth_fail_uri' => $extAuthFailUri,
		];
		$options = array_filter($options);
		return $this->sendAuthRequest('/api/process-payment', $options);
	}

	function sendPayment(string $to, float $amount)
    {
        $to = preg_replace("/[^0-9]/", '', $to);

        $paymentType = null;
        if (preg_match('/^(41001\d{9,10})$/i', $to))
            $paymentType = 'P2P';
        if (preg_match('/^((\+7|7|8)+([0-9]){10})$/i', $to))
            $paymentType = 'Phone';

        if (!$paymentType) {
            self::$responseSuccess = false;
            $result = [
                'error' => '400 Bad Request',
            ];
            return (object)$result;
        } else {
            $result = $this->{'requestPayment' . $paymentType}($to, $amount);
            if (API::$responseSuccess)
                $result = $this->processPayment($result->request_id);
            return $result;
        }
    }

	function incomingTransferAccept(string $operation_id, string $protection_code = '')
	{
		$options = [
			'operation_id' => $operation_id,
			'protection_code' => $protection_code
		];
		$options = array_filter($options);
		return $this->sendAuthRequest('/api/incoming-transfer-accept', $options);
	}

	function incomingTransferReject(string $operation_id)
	{
		return $this->sendAuthRequest('/api/incoming-transfer-reject',
			[
				'operation_id' => $operation_id,
			]);
	}

	public static function getAuthUrl($clientId, $redirectUrl, $scope = [])
	{
		$options = [
			'client_id' => $clientId,
			'response_type' => 'code',
			'redirect_uri' => $redirectUrl,
			'scope' => implode(' ', $scope),
		];
		return self::$domain . '/oauth/authorize?' . http_build_query($options);
	}

	public static function getToken($clientId, $code, $redirectUrl, $clientSecret = null)
	{
		return self::sendRequest('/oauth/token', [
			'code' => $code,
			'client_id' => $clientId,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $redirectUrl,
			'client_secret' => $clientSecret
		]);
	}

	public static function revokeToken($token)
	{
		return self::sendRequest('/api/revoke', [], $token);
	}

	public static function sendRequest($url, $options = [], $token = null)
	{
		$fullUrl = self::$domain . $url;

		$curl = curl_init($fullUrl);
		if($token !== null) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $token
			));
		}
		curl_setopt($curl, CURLOPT_POST, 1);
		$query = http_build_query($options);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		$body = curl_exec($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		switch ($statusCode) {
			case 400:
			    self::$responseSuccess = false;
			    $result = [
			        'error' => $statusCode . ' Bad Request',
                ];
				return (object)$result;
			case 401:
                self::$responseSuccess = false;
                $result = [
                    'error' => $statusCode . ' Unauthorized',
                ];
                return (object)$result;
			case 403:
                self::$responseSuccess = false;
                $result = [
                    'error' => $statusCode . ' Insufficient scope',
                ];
                return (object)$result;
			default:
				if($statusCode >= 500) {
                    self::$responseSuccess = false;
					return (object)['error' => $statusCode . ' Server error'];
				} else {
				    $result = json_decode($body);
                    self::$responseSuccess = true;
                    if (isset($result->status))
                        self::$responseSuccess = $result->status == 'success' ? true : false;
					return $result;
				}
		}
	}
}
