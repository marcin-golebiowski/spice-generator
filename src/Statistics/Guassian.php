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
class Guassian implements GuassianInterface
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

		return exp(pow($power, 2)/2);
	}

	/**
	 * Method generate a guassian distribution for a given x, mean and standard deviation
	 * 
	 * @param   integer  $x      The x value
	 * @param   integer  $mu     The mean
	 * @param   integer  $sigma  The standard deviation
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function generate($mu, $sigma)
	{
		$x = 0;

		return $this->createFunction($x, $mu, $sigma);
	}
}