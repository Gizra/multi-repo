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

      // Remove directories if they are clean.
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
      }

      $task->run();

      // Copy new .gitmodules
      $this->_copy($filename,'.gitmodules');

      // Clone

      // Create symlinks

      // Adapt DDEV config

      // Restart DDEV.

    }
}
