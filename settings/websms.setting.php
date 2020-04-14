<?php

return [
  'websms_default_location_type_id' => [
    'name'        => 'websms_default_location_type_id',
    'type'        => 'Integer',
    'html_type'   => 'text',
    'default'     => NULL,
    'add'         => '1.0',
    'title'       => ts('Default location type for new phones added by websms'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('This determines the location type used for new phones addresses added by websms.'),
  ],
  'websms_max_sms_per_message' => [
    'name'        => 'websms_max_sms_per_message',
    'type'        => 'Integer',
    'html_type'   => 'text',
    'default'     => 5,
    'add'         => '1.0',
    'title'       => ts('Maximum number of SMS per message'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('This determines the maximum number of SMS to be sent per message (for messages exceeding the 160 character SMS limit.'),
  ],
];
