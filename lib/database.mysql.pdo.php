<?php
	function db_connect() {
		if(!isset($GLOBALS['db_instance'])) {
			try {
				$GLOBALS['db_instance'] = new PDO("mysql:dbname=". DB_NAME .";host=". DB_HOST .";charset=UTF8", DB_USER, DB_PASSWORD);
				$GLOBALS['db_instance']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				die("Could not connect to the database server (cause: ". $e->getMessage() .")");
			}
			
			//@mysql_query("SET NAMES utf8", $GLOBALS['db_instance']);
		}
	}
	
	function db_change_database($database_name=false) {
		db_connect();
		
		throw new Exception("db_change_database(PDO) does not allow dynamically changing database name");
	}
	
	function esc_sql($string) {
		db_connect();
		
		if(is_array($string)) {
			return array_map('esc_sql', $string);
		}
		
		return addslashes($string);
	}
	
	function db_query($query) {
		db_connect();
		
		try {
			if(false === ($result = $GLOBALS['db_instance']->exec($query))) {
				throw new Exception("Query failed to run");
			}
			
			if(preg_match("#^\s*INSERT#si", $query)) {
				return $GLOBALS['db_instance']->lastInsertId();
			}
			
			return $result;
		} catch(PDOException $e) {
			if(defined('PRINT_SQL_ERRORS') && PRINT_SQL_ERRORS) {
				$errorInfo = $GLOBALS['db_instance']->errorInfo();
				
				die("<pre>". print_r($errorInfo, true) ."</pre>");
			}
			
			return false;
		}
	}
	
	function get_rows($query, $column_key=false) {
		db_connect();
		
		try {
			$result = $GLOBALS['db_instance']->query($query);
		} catch(Exception $e) {
			return false;
		}
		
		$rows = array();
		
		if($result->rowCount()) {
			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				if((false !== $column_key) && isset($row[ $column_key ])) {
					$rows[ $row[ $column_key ] ] = $row;
				} else {
					$rows[] = $row;
				}
			}
		}
		
		return $rows;
	}	function get_results($query, $column_key=false) { return get_rows($query, $column_key); }
	
	function get_col($query, $column_key=0) {
		db_connect();
		
		try {
			$result = $GLOBALS['db_instance']->query($query);
		} catch(Exception $e) {
			return false;
		}
		
		$rows = array();
		
		if($result->rowCount()) {
			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				if(isset($row[ $column_key ])) {
					$rows[] = $row[ $column_key ];
				} else {
					$rows[] = array_shift($row);
				}
			}
		}
		
		return $rows;
	}
	
	function get_row($query) {
		db_connect();
		
		try {
			$result = $GLOBALS['db_instance']->query($query);
		} catch(Exception $e) {
			return false;
		}
		
		if(!$result->rowCount()) {
			return false;
		}
		
		return $result->fetch(PDO::FETCH_ASSOC);
	}
	
	function get_var($query, $column=false) {
		if(false === ($row = get_row($query))) {
			return false;
		}
		
		if(false !== $column) {
			if(isset($row[ $column ])) {
				return $row[ $column ];
			}
			
			return false;
		}
		
		return array_shift($row);
	}