<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OC\DB\QueryBuilder\FunctionBuilder;

use OC\DB\QueryBuilder\QueryFunction;
<<<<<<< HEAD
=======
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
>>>>>>> stable20
use OCP\DB\QueryBuilder\IQueryFunction;

class SqliteFunctionBuilder extends FunctionBuilder {
	public function concat($x, $y): IQueryFunction {
		return new QueryFunction('(' . $this->helper->quoteColumnName($x) . ' || ' . $this->helper->quoteColumnName($y) . ')');
	}

<<<<<<< HEAD
	public function greatest($x, $y): IQueryFunction {
		return new QueryFunction('MAX(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	public function least($x, $y): IQueryFunction {
=======
	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $x
	 * @param string|ILiteral|IParameter|IQueryFunction $y
	 * @return IQueryFunction
	 */
	public function greatest($x, $y) {
		return new QueryFunction('MAX(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $x
	 * @param string|ILiteral|IParameter|IQueryFunction $y
	 * @return IQueryFunction
	 */
	public function least($x, $y) {
>>>>>>> stable20
		return new QueryFunction('MIN(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}
}
