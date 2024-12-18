<?php

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

require_once 'websms.civix.php';
$autoload = __DIR__ . '/vendor/autoload.php';

if (file_exists($autoload)) {
  require_once $autoload;
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function websms_civicrm_config(&$config) {
  _websms_civix_civicrm_config($config);

  if (isset(Civi::$statics[__FUNCTION__])) {
    return;
  }
  Civi::$statics[__FUNCTION__] = 1;
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function websms_civicrm_install() {
  OptionGroup::get()
    ->addSelect('id')
    ->addWhere('name', '=', 'sms_provider_name')
    ->addChain('OptionValue', OptionValue::create()
      ->addValue('option_group_id', '$id')
      ->addValue('name', 'websms')
      ->addValue('label', 'websms')
      ->addValue('value', 'websms.provider')
      ->addValue('is_active', TRUE)
    )
    ->setCheckPermissions(FALSE)
    ->execute();
  _websms_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function websms_civicrm_uninstall() {
  OptionGroup::get()
    ->addSelect('id')
    ->addWhere('name', '=', 'sms_provider_name')
    ->addChain('OptionValue', OptionValue::delete()
      ->addWhere('option_group_id', '=', '$id')
      ->addWhere('name', '=', 'websms')
    )
    ->setCheckPermissions(FALSE)
    ->execute();
  // TODO: convert to APIv4 once SmsProvider is available
  civicrm_api3('SmsProvider', 'get', [
    'name'                   => 'websms',
    'api.SmsProvider.delete' => [],
  ]);
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function websms_civicrm_enable() {
  OptionGroup::get()
    ->addSelect('id')
    ->addWhere('name', '=', 'sms_provider_name')
    ->addChain('OptionValue', OptionValue::update()
      ->addWhere('option_group_id', '=', '$id')
      ->addWhere('name', '=', 'websms')
      ->addValue('is_active', TRUE)
    )
    ->setCheckPermissions(FALSE)
    ->execute();
  // TODO: convert to APIv4 once SmsProvider is available
  civicrm_api3('SmsProvider', 'get', [
    'name'                   => 'websms.provider',
    'is_active'              => 0,
    'api.SmsProvider.create' => ['is_active' => 1],
  ]);
  _websms_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function websms_civicrm_disable() {
  OptionGroup::get()
    ->addSelect('id')
    ->addWhere('name', '=', 'sms_provider_name')
    ->addChain('OptionValue', OptionValue::update()
      ->addWhere('option_group_id', '=', '$id')
      ->addWhere('name', '=', 'websms')
      ->addValue('is_active', FALSE)
    )
    ->setCheckPermissions(FALSE)
    ->execute();
  // TODO: convert to APIv4 once SmsProvider is available
  civicrm_api3('SmsProvider', 'get', [
    'name'                   => 'websms.provider',
    'is_active'              => 1,
    'api.SmsProvider.create' => ['is_active' => 0],
  ]);
}
