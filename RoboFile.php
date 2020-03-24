<?php

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

    foreach ($subSites as $row) {
      list($name, $git) = $row;
      $path = "web/sites/$name";

      // Create symlink.
      $this->_symlink($path, "config/$name");

      // Copy an adapted `settings.php`
      $this->_copy('robo/settings.php', $path, true);

      $this->taskReplaceInFile("$path/settings.php")
        ->from('{{ name }}')
        ->to($name)
        ->run();
    }

    // Adapt DDEV config

    // Restart DDEV.

  }
}
