<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Morris Jobke <hey@morrisjobke.de>
 *
<<<<<<< HEAD
 * @author Joas Schilling <coding@schilljs.com>
=======
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
>>>>>>> stable20
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\DB;

class MissingPrimaryKeyInformation {
	private $listOfMissingPrimaryKeys = [];

	public function addHintForMissingSubject(string $tableName) {
		$this->listOfMissingPrimaryKeys[] = [
			'tableName' => $tableName,
		];
	}

	public function getListOfMissingPrimaryKeys(): array {
		return $this->listOfMissingPrimaryKeys;
	}
}
