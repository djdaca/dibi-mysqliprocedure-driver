<?php

namespace Dibi\Drivers;

use Dibi;

/**
 * MySqli driver with support for MySQL stored procedures producing result sets.
 *
 * Fetches only the first result set, throws away the others.
 *
 * @uses DibiMySqliDriver
 */

class MySqliprocedureDriver extends MySqliDriver implements Dibi\Driver, Dibi\ResultDriver
{
	use Dibi\Strict;
	/**
	 * Executes the SQL query.
	 * If the query is a procedure call, uses mysqli_multi_query and throws away everything after
	 * the first result set.
	 * @param  string    SQL statement
	 * @return DibiDriver|NULL
	 * @throws DibiDriverException
	 */ 
	public function query($sql)
	{
		if (!preg_match('#\s*CALL\s(.+)#i', $sql)) {
			return parent::query($sql);
		}

		@mysqli_multi_query($this->connection, $sql); // intentionally @

		if (mysqli_errno($this->connection)) {
			$this->trashMoreResults();
			throw new Dibi\DriverException(mysqli_error($this->connection), mysqli_errno($this->connection), $sql);
		}

		$this->resultSet = mysqli_store_result($this->connection);
		$this->trashMoreResults();

		return is_object($this->resultSet) ? clone $this : NULL;
	}

	/**
	 * Throws away all the remaining results from the most recent query.
	 */
	private function trashMoreResults()
	{
		if (mysqli_more_results($this->connection)) {
			while (mysqli_next_result($this->connection));
		}
	}
}