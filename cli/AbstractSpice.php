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
use Joomla\Registry\Registry;
use Wilsonge\Statistics\Guassian;

/**
 * CLI application creating the Spice sample files
 *
 * @since  1.0
 */
class AbstractSpice extends AbstractCliApplication
{
	/**
	 * The base directory to create the Spice files in.
	 *
	 * @var    string
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
	 * @var    double
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
	 * @var    double
	 * @since  1.0
	 */
	protected $pulsePosition;

	/**
	 * The pulse position.
	 *
	 * @var    double
	 * @since  1.0
	 */
	protected $pulseWidth;

	/**
	 * The standard deviation of the indctuors.
	 *
	 * @var    double
	 * @since  1.0
	 */
	protected $inductorDev;

	/**
	 * The internal resistance of the inductor in mOhms.
	 *
	 * @var    double
	 * @since  1.0
	 */
	protected $inductorRes;

	/**
	 * The value of the capacitance in nF.
	 *
	 * @var    double
	 * @since  1.0
	 */
	protected $capacitor;

	/**
	 * The standard deviation of the capacitors.
	 *
	 * @var    double
	 * @since  1.0
	 */
	protected $capDev;

	/**
	 * The internal resistance of the capacitor in mOhms.
	 *
	 * @var    double
	 * @since  1.0
	 */
	protected $capRes;

	/**
	 * The value of the inductance in nH.
	 *
	 * @var    double
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
	 * The type of pulse to inject. Options are
	 * 1. "Gaussian" which is a guassian pulse using the "pulsePosition" and "pulseWidth" vars
	 * 2. "Single" which is inputted at the end
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pulseType;

	/**
	 * The type of pulse to inject. Options are
	 * 1. "I" which is a current pulse
	 * 2. "V" which is a voltage pulse
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pulseSource;

	/**
	 * The time for the pulse to turn on and off
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $pulseOnTime;

	/**
	 * The time before the pulse should be injected
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $pulseStartTime;

	/**
	 * The time the pulse is on for
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $pulseLength;

	/**
	 * The input impedance in ohms - only for a single
	 * input
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $inputImpedance;

	/**
	 * Class constructor
	 *
	 * @since   1.0
	 */
	public function __construct(Input\Cli $input = null, Registry $config = null, CliOutput $output = null)
	{
		// Set a standard Xml output
		$this->output = ($output instanceof CliOutput) ? $output : new \Joomla\Application\Cli\Output\Xml;

		// Set up a config if we aren't provided with one
		if (!$config)
		{
			$config = new \Joomla\Registry\Registry;
		}

		// Initialize our Guassian Class
		$this->statsClass = new \Wilsonge\Statistics\Guassian;

		$this->baseDir = $config->get('baseDir', null) ? $config->get('baseDir') : JPATH_ROOT . '/spice/';
		$this->fileName = $config->get('fileName', null) ? $config->get('fileName') : 'spice.cir';
		$this->nTaps = $config->get('nTaps', null) ? $config->get('nTaps') : 50;
		$this->pulsePosition = $config->get('pulsePosition', null) ? $config->get('pulsePosition') : 24.5;
		$this->pulseWidth = $config->get('pulseWidth', null) ? $config->get('pulseWidth') : 2.5;
		$this->inductor = $config->get('inductor', null) ? $config->get('inductor') : 470.0;
		$this->inductorDev = $config->get('inductorDev', null) ? $config->get('inductorDev') : 0;
		$this->inductorRes = $config->get('inductorRes', null) ? $config->get('inductorRes') : 0;
		$this->capacitor = $config->get('capacitor', null) ? $config->get('capacitor') : 47.0;
		$this->capDev = $config->get('capDev', null) ? $config->get('capDev') : 0;
		$this->capRes = $config->get('capRes', null) ? $config->get('capRes') : 0;
		$this->pulseType = $config->get('pulseType', null) ? $config->get('pulseType') : 'Gaussian';
		$this->pulseSource = $config->get('pulseSource', null) ? $config->get('pulseSource') : 'I';
		$this->pulseOnTime = $config->get('pulseOnTime', null) ? $config->get('pulseOnTime') : 10;
		$this->pulseStartTime = $config->get('pulseStartTime', null) ? $config->get('pulseStartTime') : 100;
		$this->pulseLength = $config->get('pulseLength', null) ? $config->get('pulseLength') : 1000;
		$this->inputImpedance = $config->get('inputImpedance', null) ? $config->get('inputImpedance') : 0;

		// Calculate the perfect termination resistance
		$this->rTerm = $config->get('rTerm', null) ? $config->get('rTerm') : sqrt($this->inductor/$this->capacitor);

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
		
		if ($this->pulseSource == 'I')
		{
			$pulseAmplitudeUnits = 'A';
		}
		else
		{
			$pulseAmplitudeUnits = 'V';
		}

		$string = null;
		$pulseTimeUnits = 'ns';
		$pulseNoise = 0.1;

		$string .= "* LC transmission line with charge injection" . "\n";

		if ($this->inductorRes != 0)
		{
			$endUnit = (2 * $this->nTaps) + 1;
		}
		else
		{
			$endUnit = $this->nTaps + 1;
		}

		if ($this->pulseType == 'Gaussian')
		{
			$string .= "R" . sprintf('%03d', 1) . " 0 N" . sprintf('%03d', 1) . ' ' . number_format($this->rTerm, 6) . "\n";
			$string .= "R" . sprintf('%03d', 2) . " 0 N" . sprintf('%03d', $endUnit) . ' ' . number_format($this->rTerm, 6) . "\n";
		}
		else
		{
			$tapIndex = $this->nTaps + 1;
			$pulseAmplitude = 1;
			
			if ($this->inputImpedance != 0)
			{
				$internalResUnits = '';
				$string .= 'R' . sprintf('%03d', 2) . ' N' . sprintf('%03d', $endUnit) . ' N'  . sprintf('%03d', $endUnit + 1) . ' ' . number_format($this->inputImpedance, 6) . $internalResUnits . "\n";
			}
			
			$string .= $this->pulseSource . sprintf('%03d', 1) . ' N' . sprintf('%03d', $endUnit + 1) . ' 0 ' . 'PULSE(0.0' .  $pulseAmplitudeUnits . ' '
				. number_format($pulseAmplitude , 6) .  $pulseAmplitudeUnits . ' ' . number_format($this->pulseStartTime, 6) . $pulseTimeUnits . ' ' . number_format($this->pulseOnTime/2, 6)
				. $pulseTimeUnits . ' ' . number_format($this->pulseOnTime/2, 6)
				. $pulseTimeUnits . ' ' . $this->pulseLength . $pulseTimeUnits . ')' . "\n";

			$string .= "R" . sprintf('%03d', 1) . " 0 N001 " . number_format($this->rTerm, 6) . "\n";
		}

		$i = 0;

		while ($i < $this->nTaps)
		{
			$tap = $i + 1;
			// $this->out("tap, pulsePosition, pulseWidth, Pulse amplitude: \n" . $tap . ', ' . $this->pulsePosition . ', ' . $this->pulseWidth . ', ' . $pulseAmplitude . '\n');
			$string .= $this->generateTapComponents(
				$tap, // Number of elements
				$pulseNoise, $pulseAmplitudeUnits, $this->pulseOnTime, $pulseTimeUnits, // Pulse Params
				'nF', // Cap Param
				'nH' // Inductor Param
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
	private function generateTapComponents($tapIndex, $pulseNoise, $pulseAmplitudeUnits, $pulseOnTime, $pulseTimeUnits, $capUnits, $inductorUnits)
	{
		$string = null;
		$inductorResistance = $this->inductorRes;
		$capacitorResistance = $this->capRes;
		$pulseAmplitude = $this->statsClass->createFunction($tapIndex, $this->pulsePosition, $this->pulseWidth);

		// Internal resistance has units of milliOhms
		$internalResUnits = 'm';

		// Define the number of resistors during initial construction
		if ($this->pulseType == 'Gaussian')
		{
			$prevRes = 2;
		}
		else
		{
			if ($this->inputImpedance != 0)
			{
				$prevRes = 2;
			}
			else
			{
				$prevRes = 1;
			}
		}

		if ($inductorResistance != 0 && $capacitorResistance != 0)
		{
			$inductorNumber = (2 * $tapIndex) + $prevRes;
			$capacitorNumber = (2 * $tapIndex) + $prevRes - 1;
		}
		// If just one of inductor resistance or capacitor resistance is on
		elseif ($inductorResistance != 0 || $capacitorResistance != 0)
		{
			$inductorNumber = $tapIndex + $prevRes;
			$capacitorNumber = $tapIndex + $prevRes;
		}

		// Calculate the numbering for the internal resistance
		if ($inductorResistance != 0)
		{
			$parallel = (2 * $tapIndex) - 1;
		}
		else
		{
			$parallel = $tapIndex;
		}

		$capVal = $this->statsClass->generate($this->capacitor, $this->capDev);
		// $this->out("The capacitor value is: " . $capVal . '\n');
		$string .= 'C' . sprintf('%03d', $tapIndex) . ' 0 N'  . sprintf('%03d', $parallel) . ' ' . number_format($capVal, 6) . $capUnits . "\n";

		if ($this->pulseType == 'Gaussian')
		{
			$string .= $this->pulseSource . sprintf('%03d', $tapIndex) . ' 0 N' . sprintf('%03d', $parallel) . ' ' . 'PULSE(0.0' .  $pulseAmplitudeUnits . ' ' . number_format($pulseAmplitude, 6) .  $pulseAmplitudeUnits . ' '
				. number_format($this->pulseStartTime, 6) . $pulseTimeUnits . ' ' . number_format($pulseOnTime/2, 6) . $pulseTimeUnits . ' ' . number_format($pulseOnTime/2, 6) . $pulseTimeUnits . ' ' . $this->pulseLength . $pulseTimeUnits . ')' . "\n";
		}

		if ($capacitorResistance != 0)
		{
			$string .= 'R' . sprintf('%03d', $capacitorNumber) . ' 0 N'  . sprintf('%03d', $parallel) . ' ' . number_format($capacitorResistance, 6) . $internalResUnits . "\n";
		}

		// If we have a internal resistance in our inductance we have to reset the N values
		if ($inductorResistance != 0)
		{
			$inductorAfter = (2 * $tapIndex);

			$string .= 'R' . sprintf('%03d', $inductorNumber) . ' N' . sprintf('%03d', (2 * $tapIndex)) . ' N'  . sprintf('%03d', (2 * $tapIndex) + 1) . ' ' . number_format($inductorResistance, 6) . $internalResUnits . "\n";
		}
		else
		{
			$inductorAfter = $tapIndex + 1;
		}

		$inductorVal = $this->statsClass->generate($this->inductor, $this->inductorDev);
		$string .= 'L' . sprintf('%03d', $tapIndex) . ' N' . sprintf('%03d', $parallel) . ' N'  . sprintf('%03d', $inductorAfter) . ' ' . number_format($inductorVal, 6) . $inductorUnits . "\n";
		// $this->out("The inductor value is: " . $inductorVal . '\n');

		return $string;
	}
}
