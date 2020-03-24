<?php

use Symfony\Component\Yaml\Yaml;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{

  const GITMODULES_REGEX = '/\[submodule "web\/sites\/(.*)"\]/';

  public function fetch(string $filename)
  {
    $this->say("Hello, $filename");

    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      throw new Exception('The working directory is dirty. Please commit any pending changes.');
    }

    // Remove directories.
    $gitmodules = file_get_contents('.gitmodules');
    preg_match_all(self::GITMODULES_REGEX, $gitmodules, $matches);

    if (empty($matches[1])) {
      throw new Exception('No directories found in .gitmodules');
    }

    $directoryNames = $matches[1];

    $task = $this
      ->taskExecStack()
      ->stopOnFail();

    // Delete symlinks.
    foreach ($directoryNames as $directoryName) {
      $task->exec('rm config/'.$directoryName);

      $path = "web/sites/$directoryName";

      $task->exec("git submodule deinit -f -- $path");
      $task->exec("rm -rf .git/modules/$path");
      $task->exec("git rm $path");
    }

    $task->run();

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
      // Cleanup folder if in case we already have an older version.
      $task->exec("rm -rf $path");

      // Add submodule.
      $task->exec("git submodule add --force -b $branch $git $path");
    }

    $task->run();

    // Adapt DDEV config
    $ddevFilename = '.ddev/config.local.yaml.example';
    $ddevConfig = Yaml::parseFile($ddevFilename);
    $ddevConfig['additional_hostnames'] = [];

    foreach ($subSites as $row) {
      list($name,,) = $row;
      $path = "web/sites/$name";

      // Create symlink.
      $this->_symlink($path, "config/$name");

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

    // Restart DDEV.
    $this->_exec('ddev restart');

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
      ->exec('git submodule update --init --recursive --force')
      ->exec('git status')
      ->run();
  }
}
