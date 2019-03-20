# Install plugins from Store during composer install

## What do you need?

* Shopware Composer Installation
* Shopware Account Credentials
* PHP 7.0 or higher
* Normal Domain and not **wildcard**

## Setup

Add the plugins in the extra section in your composer.json

Example composer.json
```json
{
    "name": "shopware/composer-project",
    "description": "Project template for Shopware projects with composer",
    "type": "project",
    "license": "MIT",
    "authors": [
        {
            "name": "Shopware",
            "email": "info@shopware.com"
        }
    ],
    "autoload": {
        "classmap": [ "app/AppKernel.php" ]
    },
    "require": {
        "php": "^5.6.4||^7.0",
        "composer/installers": "^1.0",
        "shopware/shopware": "^5.4",
        "vlucas/phpdotenv": "~2.0 || ~3.3",
        "shyim/store-plugin-installer": "dev-master"
    },
    "extra": {
        "installer-paths": {
            "Plugins/Local/Backend/{$name}/": ["type:shopware-backend-plugin"],
            "Plugins/Local/Core/{$name}/": ["type:shopware-core-plugin"],
            "Plugins/Local/Frontend/{$name}/": ["type:shopware-frontend-plugin"]
        },
        "plugins": {
            "production": {
                "SwagPaymentPaypal": "3.4.5"
            }
        }
    },
    "include-path": [
        "engine/Library/"
    ],
    "config": {
        "optimize-autoloader": true,
        "process-timeout": 0
    },
    "scripts": {
        "post-root-package-install": [
           "./app/post-install.sh"
        ],
        "post-install-cmd": [
           "./app/post-install.sh"
        ],
        "post-update-cmd":[
           "./app/post-update.sh"
        ]
    }
}
```

Set following environment variables
   * ACCOUNT_USER (Shopware User)
   * ACCOUNT_PASSWORD (Shopware Password)
   * SW_STORE_PLUGIN_INSTALLER_SILENTFAIL (do not throw exceptions on errors / default: `false``)

Install the composer plugin

```bash
composer require shyim/store-plugin-installer
```

Aaaaaaaand the Plugins should be installed

## FAQ

### BinariesException-14

Reasons can be:

* You are not logged in
* The `SHOP_URL` environment variable does not equal the from account 

#### Versions

Versions can be a constraint or a exact version