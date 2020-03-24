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
      }

      $task->run();

      // Remove all submodules.
      // https://stackoverflow.com/a/34914461/750039
      $task = $this
        ->taskExecStack()
        ->stopOnFail()
        // deinit all submodules from .gitmodules
        ->exec('git submodule deinit .')
        // Remove all submodules (`git rm`) from .gitmodules
        ->exec('git submodule | cut -c43- | while read -r line; do (git rm "$line"); done')
        // delete all submodule sections from .git/config (`git config --local --remove-section`) by fetching those from .git/config
        ->exec('git config --local -l | grep submodule | sed -e \'s/^\(submodule\.[^.]*\)\(.*\)/\1/g\' | while read -r line; do (git config --local --remove-section "$line"); done')
        // Manually remove leftovers
        ->exec('rm .gitmodules')
        ->exec('rm -rf .git/modules')
        ->run();

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
        list($name, $git) = $row;
        $path = "web/sites/$name";
        $task->exec("git submodule add $git $path");
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
