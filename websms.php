<?php

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

require_once 'websms.civix.php';
require_once __DIR__ . '/vendor/autoload.php';

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
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function websms_civicrm_xmlMenu(&$files) {
  _websms_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function websms_civicrm_postInstall() {
  _websms_civix_civicrm_postInstall();
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
  _websms_civix_civicrm_uninstall();
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
  _websms_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function websms_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _websms_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function websms_civicrm_managed(&$entities) {
  _websms_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function websms_civicrm_caseTypes(&$caseTypes) {
  _websms_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function websms_civicrm_angularModules(&$angularModules) {
  _websms_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function websms_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _websms_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function websms_civicrm_entityTypes(&$entityTypes) {
  _websms_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function websms_civicrm_themes(&$themes) {
  _websms_civix_civicrm_themes($themes);
}
