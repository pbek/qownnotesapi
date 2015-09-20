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
	 * @return \OC_OCS_Result
	 */
	public static function getCapabilities() {
//		return new \OC_OCS_Result(array(
//			'capabilities' => array(
//				'qownnotes' => array(
//					'versioning' => true,
//					),
//				),
//			));
		return array(
			'qownnotes' => array(
				'versioning' => true,
			),
		);
	}
}
