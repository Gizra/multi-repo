<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{

  public function fetch(string $filename)
  {
    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      throw new Exception('The working directory is dirty. Please commit any pending changes.');
    }

    // Remove directories under web/sites.
    $finder = new Finder();
    $finder
      ->directories()
      ->in('web/sites')
      ->exclude('default')
      // Don't search sub-directories.
      ->depth('== 0');

    if ($finder->hasResults()) {
      $task = $this
        ->taskExecStack()
        ->stopOnFail();

      foreach ($finder as $fileInfo) {
        $name = $fileInfo->getFilename();
        $task->exec("rm -rf web/sites/$name");
      }

      $task->run();
    }

    // Remove symlinks under config.
    $finder = new Finder();
    $finder
      ->files()
      ->in('config')
      ->exclude('sync')
      // Don't search sub-directories.
      ->depth('== 0');

    if ($finder->hasResults()) {
      $task = $this
        ->taskExecStack()
        ->stopOnFail();

      foreach ($finder as $fileInfo) {
        $name = $fileInfo->getFilename();
        $task->exec("rm config/$name");
      }

      $task->run();
    }

    // Get new subsites from file.
    $subSites = [];
    if (($handle = fopen($filename, 'r')) !== FALSE) {
      while (($data = fgetcsv($handle)) !== FALSE) {
        $subSites[] = $data;
      }
      fclose($handle);
    }

    // Add sub-modules
    $task = $this
      ->taskExecStack()
      ->stopOnFail();

    foreach ($subSites as $row) {
      list($name, $git, $branch) = $row;

      $branch = $branch ?: 'master';

      $path = "web/sites/$name";

      // Clone sub-site.
      $task->exec("git clone $git $path --branch=$branch");
    }

    $task->run();

    // Adapt DDEV config
    $ddevFilename = '.ddev/config.local.yaml.example';
    $ddevConfig = Yaml::parseFile($ddevFilename);
    $ddevConfig['additional_hostnames'] = [];

    foreach ($subSites as $row) {
      list($name,,) = $row;
      $path = "web/sites/$name";

      // Create symlink. We have `../path`, as the symlink needs a relative
      // path.
      $this->_symlink("../$path", "config/$name");

      // Copy an adapted `settings.php`
      $this->_copy('robo/settings.php', $path.'/settings.php', true);

      $this->taskReplaceInFile("$path/settings.php")
        ->from('{{ name }}')
        ->to($name)
        ->run();

      $ddevConfig['additional_hostnames'][] = $name;
    }

    // Remove previous DDEV `post-start` commands.
    foreach ($ddevConfig['hooks']['post-start'] as $index => $row) {
      if (!empty($row['auto-generated'])) {
        unset($ddevConfig['hooks']['post-start'][$index]);
      }
    }

    // Add new DDEV `post-start` commands.
    foreach ($subSites as $row) {
      list($name,,) = $row;

      $newRows = [
        [
          'exec' => "mysql -uroot -proot -e \"CREATE DATABASE IF NOT EXISTS $name; GRANT ALL ON basic.* to 'db'@'%';\"",
          'service' => 'db',
          'auto-generated' => true,
        ],

        [
          'exec' => "drush @$name.ddev site-install server -y --existing-config --sites-subdir=$name",
          'auto-generated' => true,
        ],

        [
          'exec' => "drush @$name.ddev uli",
          'auto-generated' => true,
        ],
      ];

      $ddevConfig['hooks']['post-start'] = array_merge($ddevConfig['hooks']['post-start'], $newRows);
    }

    $yaml = Yaml::dump($ddevConfig);
    file_put_contents($ddevFilename, $yaml);

    $this->_copy($ddevFilename, '.ddev/config.local.yaml', true);
  }

  /**
   * Reset directory and git after running the `fetch` command.
   */
  public function reset() {
    $this
      ->taskExecStack()
      ->stopOnFail()
      ->exec('git reset --hard HEAD')
      ->exec('git clean -fd')
      ->exec('git status')
      ->run();
  }
}
