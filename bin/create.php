<?php
/**
 * CLI Generator for Spice files
 *
 * @copyright  Copyright (C) 2014 George Wilson. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License Version 3 or Later
 */

// Set error reporting for development
error_reporting(-1);

// Application constants
define('JPATH_ROOT', dirname(__DIR__));

// Load the Composer autoloader
if (!file_exists(JPATH_ROOT . '/vendor/autoload.php'))
{
	fwrite(STDOUT, "Composer is not set up properly, please run 'composer install'.\n");

	exit(500);
}

require JPATH_ROOT . '/vendor/autoload.php';

// Execute the application
try
{
	(new Wilsonge\Cli\Spice)->execute();
}
catch (\Exception $e)
{
	fwrite(STDOUT, "\nERROR: " . $e->getMessage() . "\n");
	fwrite(STDOUT, "\n" . $e->getTraceAsString() . "\n");

	exit($e->getCode() ? : 255);
}
