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
        throw new \Exception('The working directory is dirty. Please commit any pending changes.');
      }

      // Remove directories.
      $gitmodules = file_get_contents('.gitmodules');
      preg_match_all(self::GITMODULES_REGEX, $gitmodules, $matches);

      if (empty($matches[1])) {
        $this->say('No directories found in .gitmodules');
        return;
      }

      $directoryNames = $matches[1];

      $task = $this
        ->taskExecStack()
        ->stopOnFail();

      // Delete symlinks.
      foreach ($directoryNames as $directoryName) {
        $task->exec('rm config/'.$directoryName);

        $path = 'web/sites/'.$directoryName;

        // Remove sub-modules.
        // https://gist.github.com/myusuf3/7f645819ded92bda6677#gistcomment-2650640
        $task->exec("git submodule deinit $path");
        $task->exec("git rm $path");
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

      // Delete symlinks.
      foreach ($subSites as $row) {
        list($name, $git) = $row;
        $task->exec("git submodule add $git web/sites/$name");
      }

      $task->run();

      // Create symlinks

      // Adapt DDEV config

      // Restart DDEV.

    }
}
