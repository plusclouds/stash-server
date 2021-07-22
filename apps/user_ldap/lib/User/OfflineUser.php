<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP\User;

use OCA\User_LDAP\Mapping\UserMapping;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Share\IManager;
use OCP\Share\IShare;

class OfflineUser {
	/**
	 * @var string $ocName
	 */
	protected $ocName;
	/**
	 * @var string $dn
	 */
	protected $dn;
	/**
	 * @var string $uid the UID as provided by LDAP
	 */
	protected $uid;
	/**
	 * @var string $displayName
	 */
	protected $displayName;
	/**
	 * @var string $homePath
	 */
	protected $homePath;
	/**
	 * @var string $lastLogin the timestamp of the last login
	 */
	protected $lastLogin;
	/**
	 * @var string $foundDeleted the timestamp when the user was detected as unavailable
	 */
	protected $foundDeleted;
	/**
	 * @var string $email
	 */
	protected $email;
	/**
	 * @var bool $hasActiveShares
	 */
	protected $hasActiveShares;
	/**
	 * @var IConfig $config
	 */
	protected $config;
	/**
	 * @var IDBConnection $db
	 */
	protected $db;
	/**
	 * @var \OCA\User_LDAP\Mapping\UserMapping
	 */
	protected $mapping;
	/** @var IManager */
	private $shareManager;

	public function __construct(
		$ocName,
		IConfig $config,
		UserMapping $mapping,
		IManager $shareManager
	) {
		$this->ocName = $ocName;
		$this->config = $config;
		$this->mapping = $mapping;
<<<<<<< HEAD
		$this->shareManager = $shareManager;
=======
>>>>>>> stable20
	}

	/**
	 * remove the Delete-flag from the user.
	 */
	public function unmark() {
		$this->config->deleteUserValue($this->ocName, 'user_ldap', 'isDeleted');
		$this->config->deleteUserValue($this->ocName, 'user_ldap', 'foundDeleted');
	}

	/**
	 * exports the user details in an assoc array
	 * @return array
	 */
	public function export() {
		$data = [];
		$data['ocName'] = $this->getOCName();
		$data['dn'] = $this->getDN();
		$data['uid'] = $this->getUID();
		$data['displayName'] = $this->getDisplayName();
		$data['homePath'] = $this->getHomePath();
		$data['lastLogin'] = $this->getLastLogin();
		$data['email'] = $this->getEmail();
		$data['hasActiveShares'] = $this->getHasActiveShares();

		return $data;
	}

	/**
	 * getter for Nextcloud internal name
	 * @return string
	 */
	public function getOCName() {
		return $this->ocName;
	}

	/**
	 * getter for LDAP uid
	 * @return string
	 */
	public function getUID() {
<<<<<<< HEAD
		if ($this->uid === null) {
=======
		if (!isset($this->uid)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return $this->uid;
	}

	/**
	 * getter for LDAP DN
	 * @return string
	 */
	public function getDN() {
<<<<<<< HEAD
		if ($this->dn === null) {
=======
		if (!isset($this->dn)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return $this->dn;
	}

	/**
	 * getter for display name
	 * @return string
	 */
	public function getDisplayName() {
<<<<<<< HEAD
		if ($this->displayName === null) {
=======
		if (!isset($this->displayName)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return $this->displayName;
	}

	/**
	 * getter for email
	 * @return string
	 */
	public function getEmail() {
<<<<<<< HEAD
		if ($this->email === null) {
=======
		if (!isset($this->email)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return $this->email;
	}

	/**
	 * getter for home directory path
	 * @return string
	 */
	public function getHomePath() {
<<<<<<< HEAD
		if ($this->homePath === null) {
=======
		if (!isset($this->homePath)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return $this->homePath;
	}

	/**
	 * getter for the last login timestamp
	 * @return int
	 */
	public function getLastLogin() {
<<<<<<< HEAD
		if ($this->lastLogin === null) {
=======
		if (!isset($this->lastLogin)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return (int)$this->lastLogin;
	}

	/**
	 * getter for the detection timestamp
	 * @return int
	 */
	public function getDetectedOn() {
<<<<<<< HEAD
		if ($this->foundDeleted === null) {
=======
		if (!isset($this->foundDeleted)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return (int)$this->foundDeleted;
	}

	/**
	 * getter for having active shares
	 * @return bool
	 */
	public function getHasActiveShares() {
<<<<<<< HEAD
		if ($this->hasActiveShares === null) {
=======
		if (!isset($this->hasActiveShares)) {
>>>>>>> stable20
			$this->fetchDetails();
		}
		return $this->hasActiveShares;
	}

	/**
	 * reads the user details
	 */
	protected function fetchDetails() {
		$properties = [
			'displayName' => 'user_ldap',
			'uid' => 'user_ldap',
			'homePath' => 'user_ldap',
			'foundDeleted' => 'user_ldap',
			'email' => 'settings',
			'lastLogin' => 'login',
		];
		foreach ($properties as $property => $app) {
			$this->$property = $this->config->getUserValue($this->ocName, $app, $property, '');
		}

		$dn = $this->mapping->getDNByName($this->ocName);
		$this->dn = ($dn !== false) ? $dn : '';

		$this->determineShares();
	}

	/**
	 * finds out whether the user has active shares. The result is stored in
	 * $this->hasActiveShares
	 */
	protected function determineShares() {
<<<<<<< HEAD
		$shareInterface = new \ReflectionClass(IShare::class);
		$shareConstants = $shareInterface->getConstants();

		foreach ($shareConstants as $constantName => $constantValue) {
			if (strpos($constantName, 'TYPE_') !== 0
				|| $constantValue === IShare::TYPE_USERGROUP
			) {
				continue;
			}
			$shares = $this->shareManager->getSharesBy(
				$this->ocName,
				$constantValue,
				null,
				false,
				1
			);
			if (!empty($shares)) {
				$this->hasActiveShares = true;
				return;
			}
		}

		$this->hasActiveShares = false;
=======
		$query = $this->db->prepare('
			SELECT `uid_owner`
			FROM `*PREFIX*share`
			WHERE `uid_owner` = ?
		', 1);
		$query->execute([$this->ocName]);
		if ($query->rowCount() > 0) {
			$this->hasActiveShares = true;
			return;
		}

		$query = $this->db->prepare('
			SELECT `owner`
			FROM `*PREFIX*share_external`
			WHERE `owner` = ?
		', 1);
		$query->execute([$this->ocName]);
		$this->hasActiveShares = $query->rowCount() > 0;
>>>>>>> stable20
	}
}
