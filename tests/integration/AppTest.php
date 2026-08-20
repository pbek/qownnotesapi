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

use OCP\App\IAppManager;
use Test\TestCase;

/**
 * This test shows how to make a small Integration Test. Query your class
 * directly from the container, only pass in mocks if needed and run your tests
 * against the database.
 */
class AppTest extends TestCase {
	public function testAppInstalled(): void {
		$appManager = \OC::$server->get(IAppManager::class);
		$this->assertTrue($appManager->isInstalled('qownnotesapi'));
	}
}
