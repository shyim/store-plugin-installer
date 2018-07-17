# Install plugins from Store during composer install

## What do you need?

* Shopware Composer Installation
* Shopware Account Credentials

## Known Problems
* The current used api route can download only the latest version of the plugin

## Setup

Create a plugins.ini in the main folder of your installation with this schema

```ini
[production]
SwagPaymentPaypal=3.4.5

[staging]
SwagPaymentPaypal=3.5.0
```

Set following environment variables
   * ACCOUNT_USER (Shopware User)
   * ACCOUNT_PASSWORD (Shopware Password)

Install the composer plugin

```bash
composer require shyim/store-plugin-installer
```

Aaaaaaaand the Plugins should be installed