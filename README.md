    cp -R config/sync ./web/sites/default/files
    ddev composer install
    ddev restart


## Todos

* [] After composer install, revert changes?

    # sites/sites.php

    $sites['umami.ddev.site'] = 'umami';
    $sites['basic.ddev.site'] = 'basic';


    # sites/default/settings.php

    $config_directories[CONFIG_SYNC_DIRECTORY] = '../config/sync';
    $settings['file_private_path'] = '/var/www/private';

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


