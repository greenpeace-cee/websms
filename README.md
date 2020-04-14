# websms

[![CircleCI](https://circleci.com/gh/greenpeace-cee/websms.svg?style=svg)](https://circleci.com/gh/greenpeace-cee/websms)

Send and receive text messages in CiviCRM using [websms](https://websms.at/).

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.1+
* CiviCRM 5.19+

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/greenpeace-cee/websms.git
cd websms
composer install
cv en websms
```

## Configuration

To configure websms, first [obtain an API token](https://app.websms.com/#/api-admin/access) (not API password!).
Next, in CiviCRM, go to Administer > System Settings > SMS Providers and click on "Add SMS Provider" and
enter the following values:

* **Name:** `websms`
* **Title:** `websms`
* **Username:** `websms` *(Note: This field is not used - you may enter any value)*
* **Password:** paste the API token from the previous step
* **API Type:** `http`
* **API Url:** `https://api.websms.com/`
* **API Parameters:** `key={random_key}` *(Note: this is **not** the API token!)*

The key in the last parameter is used as a simple authentication key for inbound
messages to prevent forgeries. You may skip this step if you're not processing
inbound messages in CiviCRM or want to use a different authentication mechanism
(e.g. IP whitelisting).

You can generate a random value like this (or use another tool of your choice):

    openssl rand -hex 32

To forward incoming messages to CiviCRM, [add a new HTTP(S) forwarding rule](https://app.websms.com/#/trigger/mo/sms)
with the following settings:

* **URL:**
  * On Drupal: `https://civicrm.example.org/civicrm/sms/callback?provider_id={id_of_websms_provider}&key={random_key}`
  * On WordPress: `https://civicrm.example.org/?page=CiviCRM&q=civicrm%2Fsms%2Fcallback&provider_id={id_of_websms_provider}&key={random_key}`
* **Data format**: JSON

Replace `civicrm.example.org` with the hostname of your CiviCRM installation,
`{id_of_websms_provider}` with the ID of the websms provider in CiviCRM (hover
the "Edit" link to see it) and `{random_key}` with the random key you generated
in the previous step. If you haven't configured a key, remove the `&key={random_key}`
bit from the URL.
