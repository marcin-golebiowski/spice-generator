<?php
/**
 * Guassian Distribution Generator
 *
 * @copyright  Copyright (C) 2014 George Wilson. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License Version 3 or Later
 */

namespace Wilsonge\Statistics;

/**
 * Class to generate a Guassian
 *
 * @since  1.0
 */
class Guassian implements InputInterface
{
	/**
	 * Method generate a guassian value for a given x, mean and standard deviation
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
		$difference = $x - $mu;
		$power = $difference/$sigma;

		return exp(-pow($power, 2)/2);
	}

	/**
	 * Method generate a guassian distribution for a given x, mean and standard deviation
	 * Use the stats_rand_gen_normal() - it's probably not so we use the Box-Muller method
	 * as a fall back.
	 * 
	 * @param   integer  $x      The x value
	 * @param   integer  $mu     The mean
	 * @param   integer  $sigma  The standard deviation
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function generateFunction($mu, $sigma)
	{
		if (function_exists('stats_rand_gen_normal'))
		{
			return stats_rand_gen_normal($mu, $sigma);
		}
		else
		{
			// Set a random seed
			mt_srand($this->makeSeed());

			$a = mt_rand() / mt_getrandmax();
			$b = mt_rand() / mt_getrandmax();

			// The standard Guassian with sigma=1 and mean=0
			$gauss = sqrt(-2 * log($a)) * cos(2 * pi() * $b);
			
			return ($sigma * $gauss) + $mu;
		}
	}

	private function makeSeed()
	{
		list($usec, $sec) = explode(' ', microtime());

		return (float) $sec + ((float) $usec * 100000);	
	}
}