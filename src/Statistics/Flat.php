<?php
/**
 * Flat Distribution Generator
 *
 * @copyright  Copyright (C) 2014 George Wilson. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License Version 3 or Later
 */

namespace Wilsonge\Statistics;

/**
 * Class to generate a Flat distribution
 *
 * @since  1.0
 */
class Flat implements InputInterface
{
	/**
	 * Cache for the random number
	 * 
	 * @var    double
	 * @since  1.0
	 */
	protected static $random = 0;

	/**
	 * For a given x we just return x as the probability is equal anywhere down the line
	 * 
	 * @param   integer  $x      The x value
	 * @param   integer  $mu     The mean
	 * @param   integer  $sigma  The standard deviation
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function createFunction($x, $mu, $sigma)
	{
		return $x;
	}

	/**
	 * Method to generate a flat distribution
	 * 
	 * @param   integer  $mu     The mean
	 * @param   integer  $sigma  The standard deviation
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function generateFunction($mu, $sigma)
	{
		$seed = self::$random;

		// Set a random seed. It can only generate a random number once every microsecond
		// so we cache the result and then make sure it isn't the same as the previous one
		// becomes some computers are just too good
		while(self::$random === $seed)
		{
			$seed = $this->makeSeed();
		}

		self::$random = $seed;
		mt_srand($seed);

		$rand = mt_rand() / mt_getrandmax();
		
		return ($sigma * $rand * 2) + ($mu - $sigma);
	}

	/**
	 * Creates a time randomized number. Note that microtime() is restricted to only
	 * generating one number every microsecond. Hence why we will cache this
	 * result to get a proper randomised seed
	 * 
	 * @return  double
	 *
	 * @since   1.0
	 */
	private function makeSeed()
	{
		list($usec, $sec) = explode(' ', microtime());

		return (float) $sec + ((float) $usec * 100000);	
	}
}
