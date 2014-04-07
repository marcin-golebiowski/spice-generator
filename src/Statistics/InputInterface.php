<?php
/**
 * Input Distribution Generator
 *
 * @copyright  Copyright (C) 2014 George Wilson. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License Version 3 or Later
 */

namespace Wilsonge\Statistics;

/**
 * Guassian Interface
 *
 * @since  1.0
 */
interface InputInterface
{
	/**
	 * Method generate a distribution function for a given x, mean and standard deviation
	 * 
	 * @param   integer  $x      The x value
	 * @param   integer  $mu     The mean
	 * @param   integer  $sigma  The standard deviation
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function createFunction($x, $mu, $sigma);

	/**
	 * Method generate a random point on a distribution function for a given mean and standard deviation
	 * 
	 * @param   integer  $x      The x value
	 * @param   integer  $mu     The mean
	 * @param   integer  $sigma  The standard deviation
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function generateFunction($mu, $sigma);
}