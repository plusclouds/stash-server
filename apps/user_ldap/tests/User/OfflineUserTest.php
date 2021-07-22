<?php

declare(strict_types=1);
<<<<<<< HEAD

=======
>>>>>>> stable20
/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
<<<<<<< HEAD
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
=======
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
>>>>>>> stable20
 *
 */

namespace OCA\User_LDAP\Tests\User;

<<<<<<< HEAD
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\OfflineUser;
use OCP\IConfig;
use OCP\Share\IManager;
use OCP\Share\IShare;
=======
use Doctrine\DBAL\Driver\Statement;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\OfflineUser;
use OCP\IConfig;
use OCP\IDBConnection;
>>>>>>> stable20
use Test\TestCase;

class OfflineUserTest extends TestCase {

	/** @var OfflineUser */
	protected $offlineUser;
	/** @var UserMapping|\PHPUnit\Framework\MockObject\MockObject */
	protected $mapping;
	/** @var string */
	protected $uid;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
<<<<<<< HEAD
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $shareManager;
=======
	/** @var IDBConnection|\PHPUnit\Framework\MockObject\MockObject */
	protected $dbc;
>>>>>>> stable20

	public function setUp(): void {
		$this->uid = 'deborah';
		$this->config = $this->createMock(IConfig::class);
<<<<<<< HEAD
		$this->mapping = $this->createMock(UserMapping::class);
		$this->shareManager = $this->createMock(IManager::class);
=======
		$this->dbc = $this->createMock(IDBConnection::class);
		$this->mapping = $this->createMock(UserMapping::class);
>>>>>>> stable20

		$this->offlineUser = new OfflineUser(
			$this->uid,
			$this->config,
<<<<<<< HEAD
			$this->mapping,
			$this->shareManager
=======
			$this->dbc,
			$this->mapping
>>>>>>> stable20
		);
	}

	public function shareOwnerProvider(): array {
<<<<<<< HEAD
		return [
			[[], false],
			[[IShare::TYPE_USER], true],
			[[IShare::TYPE_GROUP, IShare::TYPE_LINK], true],
			[[IShare::TYPE_EMAIL, IShare::TYPE_REMOTE, IShare::TYPE_CIRCLE], true],
			[[IShare::TYPE_GUEST, IShare::TYPE_REMOTE_GROUP, IShare::TYPE_ROOM], true],
=======
		// tests for none, one, many
		return [
			[ 0, 0, false],
			[ 1, 0, true],
			[ 0, 1, true],
			[ 1, 1, true],
			[ 2, 0, true],
			[ 0, 2, true],
			[ 2, 2, true],
>>>>>>> stable20
		];
	}

	/**
	 * @dataProvider shareOwnerProvider
	 */
<<<<<<< HEAD
	public function testHasActiveShares(array $existingShareTypes, bool $expected) {
		$shareMock = $this->createMock(IShare::class);

		$this->shareManager->expects($this->atLeastOnce())
			->method('getSharesBy')
			->willReturnCallback(function (string $uid, int $shareType) use ($existingShareTypes, $shareMock) {
				if (in_array($shareType, $existingShareTypes)) {
					return [$shareMock];
				}
				return [];
			});
=======
	public function testHasActiveShares(int $internalOwnerships, int $externalOwnerships, bool $expected) {
		$queryMock = $this->createMock(Statement::class);
		$queryMock->expects($this->atLeastOnce())
			->method('execute');
		$queryMock->expects($this->atLeastOnce())
			->method('rowCount')
			->willReturnOnConsecutiveCalls(
				$internalOwnerships > 0 ? 1 : 0,
				$externalOwnerships > 0 ? 1 : 0
			);

		$this->dbc->expects($this->atLeastOnce())
			->method('prepare')
			->willReturn($queryMock);
>>>>>>> stable20

		$this->assertSame($expected, $this->offlineUser->getHasActiveShares());
	}
}
