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
		$this->rTerm = 3.3;
		$this->statsClass = new \Wilsonge\Statistics\Guassian;
		$this->pulsePosition = 24.5;
		$this->pulseWidth = 2.5;

		parent::__construct($input, $config, $output);
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
	protected function doExecute()
	{
		$this->out('Running ...');
		$string = $this->generateString();

		if ($string)
		{
			$this->out('Creating the files');
			$fileName = 'spice.cir';
			$this->generateFile($fileName, $string);
			
			$this->out('File generated at ' . $this->baseDir . $fileName);
			
			return;
		}

		$this->out('No files to generate!');
	}

	private function generateString()
	{
		if (!is_integer($this->nTaps) && $this->nTaps < 1)
		{
			throw new \RuntimeException('There must be at least 1 tap');
		}

		$string = null;

		$string .= "* LC transmission line with charge injection" . "\n";
		$string .= "R1 0 N001 " . $this->rTerm . "\n";
		$string .= "R2 0 N" . sprintf('%03d', $this->nTaps) . ' ' . $this->rTerm . "\n";

		$i = 0;

		while ($i < $this->nTaps)
		{
			$tap = $i + 1;
			$pulseAmplitude = $this->statsClass->createFunction($tap, $this->pulsePosition, $this->pulseWidth);
			$string .= $this->generateTapComponents($tap,
				$pulseAmplitude, 0.1, 'A', 100, 10, 'ns', // Pulse Param
				47.0, 4.7, 'nF', // Cap Param
				470.0, 47.0, 'nH' // Inductor Param
			);
			$i++;
		}

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
	 **/
	private function generateTapComponents($tapIndex, $pulseAmplitude, $pulseNoise, $pulseAmplitudeUnits, $pulseStartTime, $pulseOnTime, $pulseTimeUnits,
		$capNominal, $capSigma, $capUnits, $inductorNominal, $inductorSigma, $inductorUnits)
	{
		$string = null;

		$capVal = $this->statsClass->generate($capNominal, $capSigma);
		$string .= 'C' . sprintf('%03d', $tapIndex) . ' 0 N'  . sprintf('%03d', $tapIndex) . ' ' . number_format($capVal, 6) . $capUnits . "\n";

		$inductorVal = $this->statsClass->generate($inductorNominal, $inductorSigma);
		$string .= 'L' . sprintf('%03d', $tapIndex) . ' N' . sprintf('%03d', $tapIndex) . ' N'  . sprintf('%03d', $tapIndex + 1) . ' ' . number_format($inductorVal, 6) . $inductorUnits . "\n";

		$string .= 'I' . sprintf('%03d', $tapIndex) . ' N' . sprintf('%03d', $tapIndex) . ' 0 ' . 'PULSE ( 0.0 ' . number_format($pulseAmplitude, 6) .  $pulseAmplitudeUnits . ' '
			. number_format($pulseStartTime, 6) . $pulseTimeUnits . ' ' . number_format($pulseOnTime/2, 6) . $pulseTimeUnits . ' ' . number_format($pulseOnTime/2, 6) . $pulseTimeUnits . '  1.0ns )' . "\n";

		return $string;
	}

	private function generateFile($filename, $buffer)
	{
		File::write($this->baseDir . $filename, $buffer);
	}
}
