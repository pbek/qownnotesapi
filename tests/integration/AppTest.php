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

use OCA\QOwnNotesAPI\AppInfo\Application;
use Test\TestCase;

/**
 * This test shows how to make a small Integration Test. Query your class
 * directly from the container, only pass in mocks if needed and run your tests
 * against the database.
 */
class AppTest extends TestCase
{
    private $container;

    public function setUp()
    {
        parent::setUp();
        $app = new Application();
        $this->container = $app->getContainer();
    }

    public function testAppInstalled()
    {
        $appManager = $this->container->query('OCP\App\IAppManager');
        $this->assertTrue($appManager->isInstalled('qownnotesapi'));
    }
}
