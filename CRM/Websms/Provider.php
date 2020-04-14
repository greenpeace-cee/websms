<?php

class CRM_Websms_Provider extends CRM_SMS_Provider {


  protected $providerInfo = [];

  static private $_singleton;

  public function __construct(array $provider) {
    if (!empty($provider['provider_id'])) {
      $this->providerInfo = CRM_SMS_BAO_Provider::getProviderInfo($provider['provider_id']);
    }
  }

  public static function &singleton($providerParams = [], $force = FALSE) {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new self($providerParams);
    }
    return self::$_singleton;
  }

  public function send($recipients, $header, $message, $dncID = NULL) {
    if (empty($this->providerInfo['password']) || empty($this->providerInfo['api_url'])) {
      throw new \Exception(
        'password and api_url need to be set to use the websms provider'
      );
    }
    $smsClient = new WebSmsCom_Client(
      $this->providerInfo['password'],
      '',
      $this->providerInfo['api_url'],
      WebSmsCom_AuthenticationMode::ACCESS_TOKEN
    );
    $smsClient->setSslVerifyHost(2);
    $message  = new WebSmsCom_TextMessage(
      [$this->stripPhone($recipients)],
      $message
    );
    $result = $smsClient->send($message, \Civi::settings()->get('websms_max_sms_per_message'));
    Civi::log()->debug("[websms] Message {$result->getTransferId()} sent to {$recipients} with result {$result->getStatusCode()}: '{$result->getStatusMessage()}'");
    return $result->getTransferId();
  }

  public function inbound() {
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    $this->processInboundRequest($this->getRequest());
  }

  public function processInboundRequest(array $request) {
    // if "key" is set in the provider's api_params, check for a match against
    // the key GET/POST parameter
    if (!empty($this->providerInfo['api_params']['key'])) {
      $key = $this->retrieve('key', 'String', FALSE);
      if (empty($key)) {
        $this->sendStatusResponse(4001, 'Missing authentication parameter "key"');
      }
      if (!hash_equals($this->providerInfo['api_params']['key'], $key)) {
        $this->sendStatusResponse(4001, 'Wrong authentication key');
      }
    }

    try {
      $this->validateRequest($request);
      $activity = parent::processInbound(
        $this->normalizePhone($request['senderAddress'], $request['senderAddressType']),
        $this->getBody($request),
        $this->normalizePhone($request['recipientAddress'], $request['recipientAddressType']),
        $request['notificationId']
      );
      $this->sendStatusResponse(2000, 'ok activity_id=' . $activity->id);
    }
    catch (Exception $e) {
      $errorCode = $e instanceof UnexpectedValueException ? 4000 : 5000;
      $request = file_get_contents('php://input');
      Civi::log()->error("[websms] {$e->getMessage()}", ['request' => $request]);
      $this->sendStatusResponse($errorCode, $e->getMessage());
    }
  }

  private function sendStatusResponse($code, $message) {
    echo json_encode([
      'statusCode'    => $code,
      'statusMessage' => $message,
    ]);
    CRM_Utils_System::civiExit();
  }

  /**
   * Parse and return request parameters
   *
   * @return array
   * @throws \Exception
   */
  private function getRequest() {
    $request = file_get_contents('php://input');
    if (empty($request)) {
      throw new Exception('Empty POST body provided');
    }

    // TODO: use JSON_THROW_ON_ERROR
    $request = json_decode($request, TRUE);
    if (is_null($request)) {
      throw new Exception('Invalid JSON in POST body. Error was: ' . json_last_error());
    }

    return $request;
  }

  /**
   * Validate request parameters
   *
   * @param array $request
   *
   * @throws \UnexpectedValueException
   */
  private function validateRequest(array $request) {
    $required = [
      'messageType',
      'notificationId',
      'senderAddress',
      'senderAddressType',
      'recipientAddress',
      'recipientAddressType',
    ];

    if (!empty($request['messageType']) && $request['messageType'] == 'text') {
      $required[] = 'textMessageContent';
    }
    if (!empty($request['messageType']) && $request['messageType'] == 'deliveryReport') {
      $required[] = 'deliveryReportMessageStatus';
      $required[] = 'transferId';
    }

    $missingFields = [];
    foreach ($required as $field) {
      if (empty($request[$field])) {
        $missingFields[] = $field;
      }
    }

    if (count($missingFields) > 0) {
      throw new UnexpectedValueException(
        'Required fields are missing: ' . implode(', ', $missingFields)
      );
    }

    if (!in_array($request['messageType'], ['text', 'binary', 'deliveryReport'])) {
      throw new UnexpectedValueException("Unknown messageType {$request['messageType']}");
    }

    if ($request['senderAddressType'] != 'international') {
      throw new UnexpectedValueException("Unknown senderAddressType {$request['senderAddressType']}");
    }

    if (!in_array($request['recipientAddressType'], ['international', 'national', 'shortcode'])) {
      throw new UnexpectedValueException("Unknown recipientAddressType {$request['recipientAddressType']}");
    }
  }

  /**
   * Extract the message body from message $request
   *
   * @param array $request
   *
   * @return string
   */
  private function getBody(array $request) {
    switch ($request['messageType']) {
      case 'text':
        return $request['textMessageContent'];

      case 'binary':
        return '[unsupported binary message]';

      case 'deliveryReport':
        return "Delivery report for message {$request['transferId']}: {$request['deliveryReportMessageStatus']}";

      default:
        throw new UnexpectedValueException("Invalid messageType {$request['messageType']}");
    }
  }

  /**
   * Normalize phone number using com.cividesk.normalize (if installed)
   *
   * @param $phone
   * @param $type
   *
   * @return string
   */
  private function normalizePhone($phone, $type) {
    if (class_exists('CRM_Utils_Normalize')) {
      $tempPhone = $phone;
      // international numbers need a + prefix to be recognized by normalize
      if ($type == 'international') {
        $tempPhone = '+' . $tempPhone;
      }
      $normalizedPhone = ['phone' => $tempPhone];
      $normalize = CRM_Utils_Normalize::singleton();
      if ($normalize->normalize_phone($normalizedPhone)) {
        $phone = $normalizedPhone['phone'];
      }
    }
    return $phone;
  }

}
