<?php

return [
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
