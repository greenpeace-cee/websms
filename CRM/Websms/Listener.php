<?php

use Civi\Api4\Contact;
use Civi\Api4\Phone;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PreEvent;

class CRM_Websms_Listener {

  public static $newPhones = [];

  /**
   * Process Phone entity pre events
   *
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function pre(PreEvent $event) {
    if ($event->action != 'create' || $event->entity != 'Phone') {
      return;
    }
    if (empty($event->params['phone'])) {
      return;
    }
    if (!in_array($event->params['phone'], self::$newPhones)) {
      return;
    }
    // this is a new phone. should we update location_type_id?
    if (!empty(\Civi::settings()->get('websms_default_location_type_id'))) {
      // default location type for new phones is set, overwrite it
      $event->params['location_type_id'] = \Civi::settings()->get('websms_default_location_type_id');
    }
    $key = array_search($event->params['phone'], self::$newPhones);
    unset(self::$newPhones[$key]);
  }

  /**
   * Process inboundSMS events
   *
   * This roughly reimplements the contact matcher in CRM_SMS_Provider:::processInbound,
   * with some changes:
   *  - uses APIv4
   *  - matching contacts are explicitly ordered by lowest contact_id first
   *  - the phone number is used as the display_name, rather than adding a fake
   *    <phone>@mobile.sms email
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   *
   * @throws \Exception
   */
  public static function inboundSMS(GenericHookEvent $event) {
    $message = $event->message;
    $message->fromContactID = self::getOrCreateContact($message->from);
    $message->toContactID = self::getOrCreateContact($message->to);
  }

  public static function getOrCreateContact($phone) {
    $contact = Phone::get()
      ->addSelect('contact_id')
      ->addWhere('phone', '=', $phone)
      ->addWhere('contact.is_deleted', '=', FALSE)
      ->addOrderBy('contact_id', 'ASC')
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();

    if (!empty($contact)) {
      // matched to existing contact
      return $contact['contact_id'];
    }
    else {
      // remember phone for pre hook
      self::$newPhones[] = $phone;
      // create a new contact
      $contact = Contact::create()
        ->addValue('display_name', $phone)
        ->addChain('Phone', Phone::create()
          ->addValue('contact_id', '$id')
          ->addValue('phone', $phone)
        )
        ->setCheckPermissions(FALSE)
        ->execute()
        ->first();
      return $contact['id'];
    }
  }

}
