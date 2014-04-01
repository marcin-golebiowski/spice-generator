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
 * but with the impedance and the time delay of the circuit fixed to calculate the L & C values
 *
 * @since  1.0
 */
class Bandwidth extends AbstractXml
{
	/**
	 * Class constructor
	 *
	 * @since   1.0
	 */
	public function __construct(Registry $config = null)
	{
		$this->xmlPath = JPATH_ROOT . '/cli/bandwidth.xml';

		parent::__construct();

		// Convert the time delay to microseconds
		$timeDelay = $this->config->get('timeDelay') * pow(10, -9);
		$this->out('Time delay is : ' . $this->config->get('timeDelay'));

		// Calculate the capacitance and inductance and then conver to nH and nF
		$this->capacitor = ($timeDelay/($this->nTaps*$this->rTerm)) * pow(10, 9);
		$this->out('Capacitance is : ' . $this->capacitor);
		$this->inductor = (pow($this->rTerm, 2) * $this->capacitor);
		$this->out('Inductance is : ' . $this->inductor);
		
		$this->out('XML input taken from: ' . $this->xmlPath);
	}
}
