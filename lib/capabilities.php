<?php

declare(strict_types=1);
/**
 * Nextcloud / ownCloud - QOwnNotesAPI.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Patrizio Bekerle <patrizio@bekerle.com>
 * @copyright Patrizio Bekerle 2015-2020
 */

namespace OCA\QOwnNotesAPI;

use OCP\Capabilities\ICapability;

/**
 * Class Capabilities.
 */
class Capabilities implements ICapability {
	/**
	 * @return array
	 */
	public static function getCapabilities() {
		return [
			'qownnotes' => [
				'versioning' => true,
			],
		];
	}
}
