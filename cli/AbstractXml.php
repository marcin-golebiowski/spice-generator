<?php
/**
 * Guassian Distribution Generator
 *
 * @copyright  Copyright (C) 2014 George Wilson. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License Version 3 or Later
 */

namespace Wilsonge\Cli;

use Joomla\Registry\Registry;

/**
 * CLI application creating the Spice sample files with a 10% of the absolute value of the components
 * and a 3.3 ohm resistor
 *
 * @since  1.0
 */
class AbstractXml extends AbstractSpice
{
	/**
	 * The pecentage of the value to call the std dev.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $xmlPath;

	/**
	 * Class constructor
	 *
	 * @since   1.0
	 */
	public function __construct(Registry $config = null)
	{
		if (file_exists($this->xmlPath))
		{
			$config = new Registry;
			$config->loadFile($this->xmlPath, 'xml');
		}

		parent::__construct(null, $config);
	}
}
