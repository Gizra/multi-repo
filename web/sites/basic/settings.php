<?php
include $app_root . '/sites/default/settings.php';
if (file_exists($app_root . '/sites/default/settings.ddev.php')) {
  include $app_root . '/sites/default/settings.ddev.php';
}
$databases['default']['default']['database'] = 'basic';
$config['config_split.config_split.basic']['status'] = TRUE;
