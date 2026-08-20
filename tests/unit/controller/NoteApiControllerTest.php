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

namespace OCA\QOwnNotesAPI\Controller;

use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NoteApiControllerTest extends TestCase {
	private NoteApiController $controller;
	private IRequest&MockObject $request;
	private IUserManager&MockObject $userManager;
	private IAppManager&MockObject $appManager;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->controller = $this->createController('admin');
	}

	public function testGetAppInfo(): void {
		$this->request->expects($this->once())
			->method('getParam')
			->with('notes_path', '')
			->willReturn('');
		$this->appManager->expects($this->exactly(2))
			->method('isEnabledForUser')
			->willReturnMap([
				['files_versions', null, true],
				['files_trashbin', null, true],
			]);
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('qownnotesapi', 'installed_version', '')
			->willReturn('26.8.0');
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('version')
			->willReturn('34.0.3');

		$result = $this->controller->getAppInfo();

		$this->assertSame([
			'user' => 'admin',
			'versions_app' => true,
			'trash_app' => true,
			'versioning' => true,
			'app_version' => '26.8.0',
			'server_version' => '34.0.3',
			'notes_path_exists' => false,
		], $result);
	}

	public function testConstructorFallsBackToBasicAuthUser(): void {
		$previousAuthUser = $_SERVER['PHP_AUTH_USER'] ?? null;
		$_SERVER['PHP_AUTH_USER'] = 'basic-auth-user';

		try {
			$this->request->method('getParam')->willReturn('');
			$this->appManager->method('isEnabledForUser')->willReturn(false);
			$this->appConfig->method('getValueString')->willReturn('26.8.0');
			$this->config->method('getSystemValue')->willReturn('34.0.3');

			$result = $this->createController(null)->getAppInfo();

			$this->assertSame('basic-auth-user', $result['user']);
		} finally {
			if ($previousAuthUser === null) {
				unset($_SERVER['PHP_AUTH_USER']);
			} else {
				$_SERVER['PHP_AUTH_USER'] = $previousAuthUser;
			}
		}
	}

	public function testGetAppInfoReportsDisabledApps(): void {
		$this->request->expects($this->once())
			->method('getParam')
			->with('notes_path', '')
			->willReturn('');
		$this->appManager->method('isEnabledForUser')->willReturn(false);
		$this->appConfig->method('getValueString')->willReturn('26.8.0');
		$this->config->method('getSystemValue')->willReturn('34.0.3');

		$result = $this->controller->getAppInfo();

		$this->assertFalse($result['versions_app']);
		$this->assertFalse($result['trash_app']);
		$this->assertTrue($result['versioning']);
	}

	public function testGetTrashedNotesNormalizesDirectoryAndInvalidExtensions(): void {
		$this->request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => match ($key) {
				'dir' => '/Notes/',
				'extensions' => 'qnote',
				'sort' => 'mtime',
				'sortdirection' => '',
				default => $default,
			}
		);

		$result = $this->controller->getTrashedNotes();

		$this->assertSame('Notes', $result['directory']);
		$this->assertSame([], $result['notes']);
	}

	public function testGetTrashedNotesUsesRequestDefaults(): void {
		$this->request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => $default
		);

		$result = $this->controller->getTrashedNotes();

		$this->assertSame('', $result['directory']);
		$this->assertSame([], $result['notes']);
	}

	public function testGetTrashedNotesPreservesDirectoryWithoutSlashes(): void {
		$this->request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => $key === 'dir' ? 'Notes' : $default
		);

		$result = $this->controller->getTrashedNotes();

		$this->assertSame('Notes', $result['directory']);
		$this->assertSame([], $result['notes']);
	}

	private function createController(?string $userId): NoteApiController {
		return new NoteApiController(
			'qownnotesapi',
			$userId,
			$this->request,
			$this->userManager,
			$this->appManager,
			$this->config,
			$this->appConfig,
		);
	}
}
