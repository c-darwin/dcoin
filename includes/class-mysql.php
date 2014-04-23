<?php

if (!defined('DC'))
	die('!DC');

$global_current_block_id = 0;

class MySQLidb {

	protected $result;
	protected static $_instance;
	protected $_mysqli;
	protected $query;
	protected $logging;
	protected $AffectedRows;


	private $file;
	private $line;
	private $fns;
	private $class;
	private $method;
	public function __construct($host, $username, $password, $db, $port=3306) {
		$this->_mysqli = new mysqli($host, $username, $password, $db);
		if (mysqli_connect_error()) {
            trigger_error('Error connecting to MySQL : ' . mysqli_connect_errno() . ' ' .  mysqli_connect_error(), E_USER_ERROR);
        }
		self::$_instance = $this;
	}

	public static function getInstance() {
		return self::$_instance;
	}

	public function printsql() {
		return $this->query."<br>\n";
	}
	
	protected function reset() {
		unset($this->query);
	}

	private function parse_table ($query) {
		preg_match('/(FROM|INTO|TABLE|UPDATE)\s*`([a-z0-9_\.\`]+)`/i', $query, $m);
		if (isset($m[2]))
			return $m[2];
	}

	private function query_( $query, $self=false )
	{
		global $global_current_block_id;

		$this->query = $query;

	    if ((!$this->result = $this->_mysqli->query($this->query))) {
		    if (substr_count($this->_mysqli->error, 'Deadlock')==0)
	            trigger_error('Error performing query ' . $query . ' - Error message : ' . $this->_mysqli->error, E_USER_ERROR);
		    else {
			     usleep(10);
			     file_put_contents(ABSPATH . 'log/error_deadlock.log', date('H:i:s')." : ".$query."\n",  FILE_APPEND);
		    }
	    }

		$ini_array = parse_ini_file(ABSPATH . "config.ini", true);

		$this->logging = false;

		//debug_print( "{$global_current_block_id} >= {$ini_array['main']['log_block_id_begin']} && {$global_current_block_id} <= {$ini_array['main']['log_block_id_end']}", $this->file, $this->line,  $this->fns,  $this->class, $this->method);
		//if (!$self)
			//debug_print( '!$self', $this->file, $this->line,  $this->fns,  $this->class, $this->method);

		if (!$self && ($ini_array['main']['log'] == 1 || ($global_current_block_id >= $ini_array['main']['log_block_id_begin'] && $global_current_block_id <= $ini_array['main']['log_block_id_end'] && $ini_array['main']['log_block_id_begin'] && $ini_array['main']['log_block_id_end']))) {
			$this->logging = true;
			$this->AffectedRows = $this->getAffectedRows(true);
			debug_print( "AffectedRows= {$this->AffectedRows}", $this->file, $this->line,  $this->fns,  $this->class, $this->method);
			if ($ini_array['main']['log_tables']) {
				$table = $this->parse_table($query);
				$rexp = '/\`('.$ini_array['main']['log_tables'].')\`\s/i';
				//$rexp = '/sssss/';
				if (preg_match($rexp, $query) && $table) {
					$result00 = $this -> result;
					$res = $this->_mysqli->query("SELECT * FROM `{$table}`");
					$data = array();
					$i=0;
					while ($row = $res -> fetch_array(MYSQLI_ASSOC)) {
						foreach($row as $name=>$value)
							$data[$i][$name]=$value;
						$i++;
					}
					debug_print( '$table:'.$table."\n".print_r_hex($data), $this->file, $this->line,  $this->fns,  $this->class, $this->method);
					$this -> result = $result00;
				}
			}
		}
		return $this -> result;
	}

	public function query( $file, $line,  $fns,  $class, $method, $query, $type = '', $data_array = array() )
	{
		$this->file = $file;
		$this->line = $line;
		$this->fns = $fns;
		$this->class = $class;
		$this->method = $method;

		// не логируем my_keys и my_node_keys, т.к. логи могут выкладываться в паблик
		if (!preg_match('/(my_keys|my_node_keys)/i', $query))
			debug_print( $query, $file, $line,  $fns,  $class, $method);

		$this->query = $query;
		switch ($type) {
			
			case 'num_rows':
				
				$result = $this -> query_( $query );
				return $result->num_rows;
				break;
				
			case 'fetch_one':
			
				$result = $this -> query_( $query );
				$row = $result->fetch_array( MYSQLI_NUM );
				return $row[0];
				break;
			
			case 'fetch_array':
			
				$result = $this -> query_( $query );
				return $result->fetch_array( MYSQLI_ASSOC );
				break;

			case 'list':

				$result_ = array();
				$result = $this -> query_( $query );
				while ( $row = $result->fetch_array( MYSQLI_ASSOC ) ) {
					$result_[$row[$data_array[0]]] = $row[$data_array[1]];
				}
				return $result_;
				break;

			case 'array':

				$result = $this -> query_( $query );
				$result_ = array();
				while ( $row = $result->fetch_array( MYSQLI_NUM ) )
					$result_[] = $row[0];
				return $result_;
				break;

			case 'all_data':

				$result = $this -> query_( $query );
				$result_ = array();
				$i=0;
				while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
					foreach($row as $name=>$value)
						$result_[$i][$name]=$value;
					$i++;
				}
				return $result_;
				break;

			default:

				return $this -> query_( $query );
		}
	}

	public function getInsertId ()
	{
		$InsertId = $this->_mysqli->insert_id;
		debug_print( 'getInsertId:'.$InsertId, $this->file, $this->line,  $this->fns,  $this->class, $this->method);
        	return $InsertId;
    }

	public function getNumRows ( $result )
	{
		return $result->num_rows;
	}

	public function getAffectedRows ($self=false)
	{
		if (!$self && $this->logging){ // иначе будет браться affected_rows от запроса affected_rows
			debug_print( "logging AffectedRows= {$this->AffectedRows}", $this->file, $this->line,  $this->fns,  $this->class, $this->method);
			return $this->AffectedRows;
		} else
			return $this->_mysqli->affected_rows;
	}


	public function escape ( $str )
	{
		return $this->_mysqli->real_escape_string ( $str );
	}
	
	function escape_array ($array)
	{
		foreach ($array as $k => $v) {
			$array[$k] = $this->_mysqli->real_escape_string ($v);
		}		
		return $array;
	}

	public function fetchArray($result)
	{
		if ($result)
			$row = $result -> fetch_array(MYSQLI_ASSOC);
		return $row;
	}
	public function fetchArrayNum($result)
	{
		if ($result)
			$row = $result -> fetch_array(MYSQLI_NUM);
		return $row;
	}

	public function __destruct() {
		$this->_mysqli->close();
	}
}