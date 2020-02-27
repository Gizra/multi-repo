## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)

## Installation

    ddev composer install
    cp .ddev/config.local.yaml.example .ddev/config.local.yaml
    ddev restart

Notice that in the end of the `ddev restart` we get a one time admin link to login.

## Todos

    mkdir -p web/sites/default/files/sync
    cp -R config/sync ./web/sites/default/files
    ddev composer install
    git checkout web/sites/default/default.settings.php
    ddev restart

    # sites/default/settings.php

    $settings['config_sync_directory'] = '../config/sync';
    $settings['file_private_path'] = '/var/www/private';


    # sites/sites.php

    $sites['umami.ddev.site'] = 'umami';
    $sites['basic.ddev.site'] = 'basic';

    # sites/umami/settings.php

    <?php
    include $app_root . '/sites/default/settings.php';
    if (file_exists($app_root . '/sites/default/settings.ddev.php')) {
      include $app_root . '/sites/default/settings.ddev.php';
    }
    $databases['default']['default']['database'] = 'umami';

    # sites/basic/settings.php

    <?php
    include $app_root . '/sites/default/settings.php';
    if (file_exists($app_root . '/sites/default/settings.ddev.php')) {
      include $app_root . '/sites/default/settings.ddev.php';
    }
    $databases['default']['default']['database'] = 'basic';


