<?php
/**
 * Guassian Distribution Generator
 *
 * @copyright  Copyright (C) 2014 George Wilson. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License Version 3 or Later
 */

namespace Wilsonge\Cli;

/**
 * CLI application creating the Spice sample files with a 10% of the absolute value of the components
 * and a 3.3 ohm resistor
 *
 * @since  1.0
 */
class Model extends AbstractXml
{
	/**
	 * Class constructor
	 *
	 * @since   1.0
	 */
	public function __construct(Registry $config = null)
	{
		$this->xmlPath = JPATH_ROOT . '/cli/model.xml';

		parent::__construct();
		
		$this->out('XML input taken from: ' . $this->xmlPath);
	}
}
