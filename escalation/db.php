<?php
/**
* Database class that contains all the functions that might be needed by the code to facilitate some queries
**/
abstract class DbBlueprint {
	public $dbh;

	abstract protected function connectToDB();
	abstract public function checkTable($table);
	abstract protected function checkColumn($table, $column);
	abstract public function insert($rows, $table, $replace = FALSE);
	abstract public function fetchAllRows($table, $sortby_column = FALSE, $sortby_direction = FALSE);
	abstract public function checkRow($rows, $table);
	abstract public function getValue($column, $table, $param = FALSE);
	abstract public function getRow($param, $table, $single = TRUE, $random = false, $order_column = FALSE, $order_direction = 'DESC');
	abstract public function updateValue($new_value, $param, $table);
	abstract public function countRows($param, $table);
	abstract public function deleteRow($param, $table);
}

class Db extends DbBlueprint {

	public function __construct() {
		$this -> connectToDB();
	}

	/**
	* Function that connects to the database
	**/
	protected function connectToDB() {
		try {
   			$dbh = new PDO("mysql:host=localhost;dbname=osticket;charset=utf8", "root", "acidburn1@");
    		$dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    		$this -> dbh = $dbh;
		} catch(PDOException $e) {
    		if(DEBUG_ALL) echo 'PDO Error: ' . $e->getMessage();
		}
	}

	/**
	* Querying the database to check if the table supplied exists
	* @param string $table Table to check if exists
	**/
	public function checkTable($table) {
		$table_check_query = $this -> dbh -> prepare("SHOW TABLES LIKE :table");
		$table_check_query -> bindParam(":table", $table);
		$table_check_query -> execute();

		if($table_check_query -> rowCount() < 1) {
			return false;
		}

		return true;
	}

	/**
	* Querying the database to check if the column supplied exists
	* @param string $table  Table from which the column should exist
	* @param string $column Column to check if exists
	*
	**/
	public function checkColumn($table, $column) {
		$this -> checkTable($table);

		$column_check_query = $this -> dbh -> prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
		$column_check_query -> bindParam(":column", $column);
		$column_check_query -> execute();

		if($column_check_query -> rowCount() < 1) {
			return false;
		}

		else {
			return true;
		}
	}

	/**
	* Insert rows into specified table
	* @param array  $rows    rows to insert with format of 'column' => 'value'
	* @param string $table   table where the rows are going to be inserted
	* @param bool   $replace whether to run replace or insert
	**/
	public function insert($rows, $table, $replace = false) {
		if(!is_array($rows) || !count($rows))
			throw new Exception("Parameter given to the insert function of the database class is not an array");
		$this -> checkTable($table);

		$array_keys = array_keys($rows);

		// Removing any misc characters
		$insert_columns = "`" . implode("`,`", $array_keys) . "`";
		$insert_vals = ':' . implode(',:', $array_keys);

		if(!$replace) $sql_query_string = "INSERT INTO `{$table}` ({$insert_columns}) VALUES ({$insert_vals})";
		else if($replace) $sql_query_string = "REPLACE INTO `{$table}` ({$insert_columns}) VALUES ({$insert_vals})";
		$sql_query = $this -> dbh -> prepare($sql_query_string);
		$sql_query -> execute(array_combine(explode(',', $insert_vals), array_values($rows)));
	}


	/**
	* Return an array with all the rows inside a certain table
	* @param  string $table 			  table from which the data should be extracted
	* @param  string $sortby_column 	  column that the sorting is going to be based upon
	* @param  string $sortby_direction    ascending/descending
	* @return array  					  array containing all the rows from the query
	**/
	public function fetchAllRows($table, $sortby_column = FALSE, $sortby_direction = FALSE) {
		$this -> checkTable($table);

		if($sortby_column && !$sortby_direction || !$sortby_column && $sortby_direction)
			throw new Exception("Function fetchAllRows expects either one, or three parameters (sort by column and sort by direction -- asc/desc)");

		if($sortby_column && $sortby_direction && $sortby_direction != 'asc' && $sortby_direction != 'desc')
			throw new Exception("Function fetchAllRows expects value for sortby direction to be either 'asc' or 'desc'");

		if($sortby_column) $this -> checkColumn($table, $sortby_column);

		if($sortby_column) $sql_query_string = "SELECT * FROM `{$table}` ORDER BY {$sortby_column} {$sortby_direction}";
		else 			   $sql_query_string = "SELECT * FROM `{$table}`";

		$sql_query = $this -> dbh -> prepare($sql_query_string);
		$sql_query -> execute();

		return $sql_query -> fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	* Checks a certain row against the database and returns true if it exists
	* @param  array  $rows  array containing the rows that need to be checked using the syntax column => content
	* @param  string $table string containing the table that should have the row
	* @return bool          returns true if the row exists, false otherwise
	**/
	public function checkRow($rows, $table) {
		$keys = array();
		$this -> checkTable($table);

		if(!is_array($rows) || empty($rows))
			throw new Exception("Invalid rows array supplied to the checkRow function");

		$array_keys = array_keys($rows);

		foreach($array_keys as $key) {
			$this -> checkColumn($table, $key);
			$keys[] = ":{$key}";
			$conditions[] = "`{$key}` = :{$key}";
		}

		$final_conditions = implode(" AND ", $conditions);

		$sql_query_string = "SELECT * FROM `{$table}` WHERE {$final_conditions}";
		$sql_query = $this -> dbh -> prepare($sql_query_string);
		$sql_query -> execute(array_combine($keys, array_values($rows)));

		if($sql_query -> rowCount() > 0) return true;
		else return false;
 	}

	public function joinSelect($tables, $conditions) {
		if(!is_array($tables) || !is_array($conditions) || count($tables) != count($conditions)) return false;
		foreach($tables as $table) {
			if(!$this -> checkTable($table)) return false;
		}

		foreach($conditions as $k => &$condition) {
			if(!$this -> checkColumn($tables[$k], $condition)) return false;
		}

		$sql_query = $this -> dbh -> prepare("SELECT * FROM `{$tables[0]}` INNER JOIN `{$tables[1]}` ON `{$tables[0]}`.{$conditions[0]} = `{$tables[1]}`.{$conditions[1]}");
		$sql_query -> execute();
		return $sql_query -> fetchAll();
	}

	/**
	* Function that returns one specific value
	* @param  string $column column which we want to extract
	* @param  string $table  table from which the extraction should take place
	* @param  array  $param  conditions for query
	* @return string         value for the row
	**/
	public function getValue($column, $table, $param = FALSE) {
		if($param) $this -> checkRow($param, $table);
		$this -> checkColumn($table, $column);

		if($param) {
			foreach($param as $key => $value) {
				$keys[] = ":{$key}";
				$conditions[] = "`{$key}` = :{$key}";
			}

			$final_conditions = implode(" AND ", $conditions);
			$sql_query_string = "SELECT {$column} FROM `{$table}` WHERE {$final_conditions}";
			$sql_query = $this -> dbh -> prepare($sql_query_string);
			$sql_query -> execute(array_combine($keys, array_values($param)));
		}

		else {
			$sql_query_string = "SELECT {$column} FROM `{$table}` ORDER BY `id` DESC LIMIT 0,1;";
			$sql_query = $this -> dbh -> prepare($sql_query_string);
			$sql_query -> execute();
		}

		$result_array = $sql_query -> fetch();
		return $result_array[$column];
	}


	/**
	* Function that returns a single row
	* @param  array|string  $param  array containing the parameters or string if it's custom
	* @param  string 		$table  string containing the name of the table
	* @param  bool   		$single true if it's only one row, false if it's multiple
	* @return array         array containing the row
	**/
	public function getRow($param, $table, $single = true, $random = false, $orderColumn = FALSE, $orderDirection = 'DESC') {
		$this -> checkTable($table);

		if(is_array($param)) {
			$array_keys = array_keys($param);

			foreach($array_keys as $key) {
				$this -> checkColumn($table, $key);

				if(is_array($param[$key])) {
					$keys[] = ":{$key}1";
					$keys[] = ":{$key}2";

					$i = 0;
					foreach($param[$key] as $cond) {
						$i++;
						$condition[] = "`{$key}` = :{$key}{$i}";
						$param[$key . $i] = $cond[0];
					}

					$conditions[] = '(' . implode(' OR ', $condition) . ')';
					unset($param[$key]);
				}

				else {
					$keys[] = ":{$key}";
					$conditions[] = "`{$key}` = :{$key}";
				}
			}

			$final_conditions = implode(" AND ", $conditions);
		}

		else $final_conditions = $param;

		if(!is_bool($orderColumn)) {
			$this -> checkColumn($table, $orderColumn);
			$order = " ORDER BY `{$orderColumn}` {$orderDirection}";
		}
		else $order = '';

		$sql_query_string = "SELECT * FROM `{$table}` WHERE {$final_conditions} {$order}";
		if($random) $sql_query_string .= " ORDER BY RAND()";
		if($single) $sql_query_string .= " LIMIT 0,1;";

		$sql_query = $this -> dbh -> prepare($sql_query_string);

		if(is_array($param)) $sql_query -> execute(array_combine($keys, array_values($param)));
		else $sql_query -> execute();

		if($single) return $sql_query -> fetch(PDO::FETCH_ASSOC);
		else return $sql_query -> fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	* Function that updates the value of a certain row
	* @param array  $new_value array containing the new updated values
	* @param array  $param     array containing the parameters for the row to be updated
	* @param string $table     table that contains the row
	**/
	public function updateValue($new_value, $param, $table) {
		$this -> checkRow($param, $table);

		if(empty($new_value))
			throw new Exception("Please give a valid parameter for updateValue");

		$new_array_keys = array_keys($new_value);

		foreach($new_array_keys as $new_key) {
			$this -> checkColumn($table, $new_key);
			$keys[] = ":set{$new_key}";
			$set_conditions[] = "`{$new_key}` = :set{$new_key}";
		}

		$final_set_conditions = implode(", ", $set_conditions);

		$param_array_keys = array_keys($param);
		foreach($param_array_keys as $p_key) {
			$keys[] = ":where{$p_key}";
			$where_conditions[] = "`{$p_key}` = :where{$p_key}";
		}

		$final_where_conditions = implode(" AND ", $where_conditions);

		$sql_query_string = "UPDATE `{$table}` SET {$final_set_conditions} WHERE {$final_where_conditions}";
		$sql_query = $this -> dbh -> prepare($sql_query_string);
		$array_values = array_merge(array_values($new_value), array_values($param));
		$sql_query -> execute(array_combine($keys, $array_values));
	}

	/**
	* Function that deletes a row(s)
	* @param  array|string  $param array containing the parameters or a string containing a custom where condition
	* @param  string 		$table string containing the name of the table
	**/
	public function deleteRow($param, $table) {
		$this -> checkTable($table);

		if(is_array($param)) {
			$array_keys = array_keys($param);

			foreach($array_keys as $key) {
				$this -> checkColumn($table, $key);
				$keys[] = ":{$key}";

				if(is_array($param[$key])) {
					switch(key($param[$key])) {
						case 'lt':
							$operator = " < ";
							break;
						case 'gt':
							$operator = " > ";
							break;
						case 'lte':
							$operator = " <= ";
							break;
						case 'gte':
							$operator = " >= ";
							break;
						default:
							$operator = " = ";
							break;
					}

					$param[$key] = array_values($param[$key]);
					$param[$key] = $param[$key][0];

					$conditions[] = "`{$key}`{$operator}:{$key}";
				}

				else $conditions[] = "`{$key}` = :{$key}";
			}

			$final_conditions = implode(" AND ", $conditions);
			$sql_query_string = "DELETE FROM `{$table}` WHERE {$final_conditions}";
			$sql_query = $this -> dbh -> prepare($sql_query_string);
			$sql_query -> execute(array_combine($keys, array_values($param)));
		}

		else {
			$sql_query_string = "DELETE FROM `{$table}` WHERE {$param}";
			$sql_query = $this -> dbh -> prepare($sql_query_string);
			$sql_query -> execute();
		}
	}


		/**
	* Function that counts the nubmber of rows
	* @param  array|string  $param  array containing the parameters or string if it's custom
	* @param  string 		$table  string containing the name of the table
	**/
	public function countRows($param, $table) {
		$results = $this -> getRow($param, $table, FALSE);
		return count($results);
	}

	public function lastId() {
		return $this -> dbh -> lastInsertId('id');
	}

	public function returnColumns($table) {
		if(!$this -> checkTable($table)) return false;
		$q = $this -> dbh->prepare("DESCRIBE `{$table}`");
		$q->execute();
		$table_fields = $q->fetchAll(PDO::FETCH_COLUMN);
		return $table_fields;
	}

	public function returnTables() {
		$q = $this -> dbh -> query("show tables WHERE `Tables_in_" . get_setting('database_name') . "` NOT IN ('tables', 'field_types', 'users')");
		return $q -> fetchAll(PDO::FETCH_ASSOC);
	}
}
