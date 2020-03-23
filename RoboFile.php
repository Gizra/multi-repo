<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    public function fetch(string $filename)
    {
      $this->say("Hello, $filename");

      // Remove directories if they are clean.

      // Remove symlinks.

      // Copy new .gitmodules

      // Clone

      // Create symlinks

      // Adapt DDEV config

      // Restart DDEV.

    }
}
