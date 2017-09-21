<?php
/**
 * ownCloud - qownnotesapi
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Patrizio Bekerle <patrizio@bekerle.com>
 * @copyright Patrizio Bekerle 2015
 */

namespace OCA\QOwnNotesAPI;
use OCP\Capabilities\ICapability;


/**
 * Class Capabilities
 *
 * @package OCA\QOwnNotesAPI
 */
class Capabilities implements ICapability {

	/**
	 * @return array
	 */
	public static function getCapabilities() {
		return array(
			'qownnotes' => array(
				'versioning' => true,
			),
		);
	}
}
