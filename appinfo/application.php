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

namespace OCA\QOwnNotesAPI\AppInfo;

use OCP\AppFramework\App;
use OCA\QOwnNotesAPI\Capabilities;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('qownnotesapi', $urlParams);
//		$container = $this->getContainer();
//		$server = $container->getServer();

		/*
		 * Register capabilities
		 */
//		$server->getCapabilitiesManager()->registerCapability(function() {
//			return new Capabilities();
//		});

	}
}
