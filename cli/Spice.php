<?php
/**
 * Guassian Distribution Generator
 *
 * @copyright  Copyright (C) 2014 George Wilson. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License Version 3 or Later
 */

namespace Wilsonge\Cli;

use Joomla\Application\AbstractCliApplication;
use Joomla\Application\Cli\Output\Xml;
use Joomla\Filesystem\File;
use Wilsonge\Statistics\Guassian;

/**
 * CLI application creating the Spice sample files
 *
 * @since  1.0
 */
class Spice extends AbstractCliApplication
{
	/**
	 * The number of files to produce.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $fileNumber;

	/**
	 * The base directory to create the Spice files in.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $baseDir;

	/**
	 * The number of taps.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $nTaps;

	/**
	 * The terminating resistance value.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $rTerm;

	/**
	 * The instance of the Gaussian Statistics Class to get.
	 *
	 * @var    GuassianInterface
	 * @since  1.0
	 */
	protected $statsClass;

	/**
	 * The pulse width.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $pulsePosition;

	/**
	 * The pulse position.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $pulseWidth;

	/**
	 * The standard deviation of the capacitors.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $capDev;

	/**
	 * The standard deviation of the indctuors.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $inductorDev;

	/**
	 * The value of the capacitance in nF.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $capacitor;

	/**
	 * The value of the inductance in nH.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $inductor;

	/**
	 * The fileName to store the data in.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $fileName;
	/**
	 * Class constructor
	 *
	 * @since   1.0
	 */
	public function __construct(Input\Cli $input = null, Registry $config = null, CliOutput $output = null)
	{
		// Set a standard Xml output
		$this->output = ($output instanceof CliOutput) ? $output : new \Joomla\Application\Cli\Output\Xml;
		
		$this->baseDir = JPATH_ROOT . '/spice/';
		$this->nTaps = 50;
		$this->statsClass = new \Wilsonge\Statistics\Guassian;
		$this->pulsePosition = 24.5;
		$this->pulseWidth = 2.5;
		$this->inductor = 470.0;
		$this->inductorDev = 0;
		$this->capacitor = 47.0;
		$this->capDev = 0;
		$this->fileName = 'spice.cir';

		// Calculate the perfect termination resistance
		$this->rTerm = sqrt($this->inductor/$this->capacitor);

		parent::__construct($input, $config, $output);
	}

	/**
	 * Generates the required string to a file and saves it
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function doExecute()
	{
		$this->out('Running ...');
		$string = $this->generateString();

		if ($string)
		{
			$this->out('Creating the files');
			$fileName = $this->fileName;

			// Write the file
			$path = $this->baseDir . $fileName;
			File::write($path, $string);
			$this->out('File generated at ' . $path);
			
			return;
		}

		$this->out('No files to generate!');
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function generateString()
	{
		if (!is_integer($this->nTaps) && $this->nTaps < 1)
		{
			throw new \RuntimeException('There must be at least 1 tap');
		}

		$string = null;

		$string .= "* LC transmission line with charge injection" . "\n";
		$string .= "R1 0 N001 " . number_format($this->rTerm, 6) . "\n";
		$string .= "R2 0 N" . sprintf('%03d', $this->nTaps + 1) . ' ' . number_format($this->rTerm, 6) . "\n";

		$i = 0;

		while ($i < $this->nTaps)
		{
			$tap = $i + 1;
			$pulseAmplitude = $this->statsClass->createFunction($tap, $this->pulsePosition, $this->pulseWidth);
			// $this->out("tap, pulsePosition, pulseWidth, Pulse amplitude: \n" . $tap . ', ' . $this->pulsePosition . ', ' . $this->pulseWidth . ', ' . $pulseAmplitude . '\n');
			$string .= $this->generateTapComponents($tap,
				$pulseAmplitude, 0.1, 'A', 100, 10, 'ns', // Pulse Param
				$this->capacitor, $this->capDev, 'nF', // Cap Param
				$this->inductor, $this->inductorDev, 'nH' // Inductor Param
			);
			$i++;
		}

		$string .= '.tran 100ps 5us' . "\n";
		$string .= '.end';

		return $string;
	}

	/**
	 * Generates a string with spice elements for Capacitor, Inductor , Current-source
     * Current-source has a triangular pulse that starts at PulseStartTime and has width PulseOnTime
     * Capacitor has value CapNominal, Gaussian smeared by CapSigma. Similar for Inductor
     * Capacitor goes from node TapIndex to ground. Inductor goes from node TapIndex to node TapIndex+1
	 *
	 * @return  string
	 */
	private function generateTapComponents($tapIndex, $pulseAmplitude, $pulseNoise, $pulseAmplitudeUnits, $pulseStartTime, $pulseOnTime, $pulseTimeUnits,
		$capNominal, $capSigma, $capUnits, $inductorNominal, $inductorSigma, $inductorUnits)
	{
		$string = null;

		$capVal = $this->statsClass->generate($capNominal, $capSigma);
		// $this->out("The capacitor value is: " . $capVal . '\n');
		$string .= 'C' . sprintf('%03d', $tapIndex) . ' 0 N'  . sprintf('%03d', $tapIndex) . ' ' . number_format($capVal, 6) . $capUnits . "\n";

		$inductorVal = $this->statsClass->generate($inductorNominal, $inductorSigma);
		$string .= 'L' . sprintf('%03d', $tapIndex) . ' N' . sprintf('%03d', $tapIndex) . ' N'  . sprintf('%03d', $tapIndex + 1) . ' ' . number_format($inductorVal, 6) . $inductorUnits . "\n";
		// $this->out("The inductor value is: " . $inductorVal . '\n');

		$string .= 'I' . sprintf('%03d', $tapIndex) . ' N' . sprintf('%03d', $tapIndex) . ' 0 ' . 'PULSE (0.0' .  $pulseAmplitudeUnits . ' ' . number_format($pulseAmplitude, 6) .  $pulseAmplitudeUnits . ' '
			. number_format($pulseStartTime, 6) . $pulseTimeUnits . ' ' . number_format($pulseOnTime/2, 6) . $pulseTimeUnits . ' ' . number_format($pulseOnTime/2, 6) . $pulseTimeUnits . ' 1.0ns)' . "\n";

		return $string;
	}
}
