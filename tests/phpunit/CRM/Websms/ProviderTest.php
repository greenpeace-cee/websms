<?php

use Civi\Api4\LocationType;
use Civi\Api4\Phone;
use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test websms provider
 *
 * @group headless
 */
class CRM_Websms_ProviderTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use Api3TestTrait;

  private $providerId;
  private $provider;

  const SAMPLE_REQUEST = [
    'messageType'          => 'text',
    'notificationId'       => '9eca31715a1c896cbc1b',
    'senderAddress'        => '436801234567',
    'recipientAddress'     => '0800911971',
    'recipientAddressType' => 'national',
    'senderAddressType'    => 'international',
    'textMessageContent'   => 'message content',
  ];

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->providerId = $this->callAPISuccess('SmsProvider', 'create', [
      'name'       => 'websms_provider',
      'title'      => 'websms',
      // TODO: older versions of Civi expect this as int, replace with "http" once we don't care about those
      'api_type'   => CRM_Core_PseudoConstant::getKey('CRM_SMS_DAO_Provider', 'api_type', 'http'),
      'api_params' => 'key=hunter2',
    ])['id'];
    $this->provider = CRM_Websms_Provider::singleton([
      'provider_id' => $this->providerId,
    ]);
    $_REQUEST['key'] = 'hunter2';
    $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'display_name' => '0800911971',
      'api.Phone.create' => ['phone' => '0800911971'],
    ]);
    parent::setUp();
  }

  public function tearDown() {
    unset($_REQUEST['key']);
    parent::tearDown();
  }

  public function requestResponseDataProvider() {
    return [
      [
        [],
        [
          'statusCode'    => 4001,
          'statusMessage' => 'Missing authentication parameter "key"',
        ],
        NULL,
      ],
      [
        [],
        [
          'statusCode'    => 4001,
          'statusMessage' => 'Wrong authentication key',
        ],
        'wrong',
      ],
      [
        [],
        [
          'statusCode'    => 4000,
          'statusMessage' => 'Required fields are missing: messageType, notificationId, senderAddress, senderAddressType, recipientAddress, recipientAddressType',
        ],
      ],
    ];
  }

  /**
   * @dataProvider requestResponseDataProvider
   *
   * @param array $request
   * @param array $response
   * @param string $key
   */
  public function testInboundRequestResponse(array $request, array $response, $key = 'hunter2') {
    $_REQUEST['key'] = $key;
    $this->expectOutputString(
      json_encode($response)
    );
    try {
      $this->processInboundRequest($request);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // ignore
    }
  }

  public function testInboundEntityResult() {
    $this->expectOutputRegex('/^{"statusCode":2000,"statusMessage":"ok activity_id=(\d+)"}/');
    $this->processInboundRequest();
    $toContact = $this->callAPISuccess('Contact', 'getsingle', ['phone' => '0800911971']);
    $fromContact = $this->callAPISuccess('Contact', 'getsingle', ['phone' => '436801234567']);
    $activity = $this->callAPISuccess('Activity', 'getsingle', [
      'phone_number'      => '436801234567',
      'target_contact_id' => $fromContact['id'],
    ]);
    $this->assertEquals('message content', $activity['details']);
    $this->assertEquals('9eca31715a1c896cbc1b', $activity['result']);
    $this->assertEquals($toContact['id'], $activity['source_contact_id']);
  }

  private function processInboundRequest(array $request = self::SAMPLE_REQUEST) {
    try {
      $this->provider->processInboundRequest($request);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // ignore
    }
  }

}
