<?php
	class Database{
		public $connect, $query;
		private $DB_host, $DB_user, $DB_pass, $DB_name;
		public $DB_prefix;
		
		public function __construct($_config)
		{
			$this->DB_prefix 	= $_config['DB_prefix'];
			$this->DB_host 	= $_config['DB_host'];
			$this->DB_user 	= $_config['DB_user'];
			$this->DB_pass 	= $_config['DB_pass'];
			$this->DB_name 	= $_config['DB_name'];
		}
		
		// The Conection function
		public function connect()
		{
			$this->connect = @mysqli_connect($this->DB_host, $this->DB_user, $this->DB_pass); //Why @ ? Because @ is the error suppression operator and hides warnings / errors that the function outputs.
			if(!$this->connect)
			{
				die('Can\'t Connect The Server');
			}else{
				$this->query("SET NAMES 'utf8'");
				}
		}
		
		// Select DataBase for name
		public function select_db()
		{
			$select_db = @mysqli_select_db($this->connect,$this->DB_name);
			if(!$select_db)
			{
				die('The DataBase ('.$this->DB_name.') Not exists');
			}
		}
		
		// Close the Conection
		public function close()
		{
			$close = @mysqli_close($this->connect);
			return $close;
		}
		
		// MySQL Query
		public function query($sqlquery)
		{
			$query = mysqli_query($this->connect, $sqlquery);
			if(!$query)
				die($sqlquery.'<br><br>'.mysqli_error($this->connect));
			else
				return $query;
		}
		
		// Select Query
		public function select($table, $fields = '*', $where = '', $orderby = '', $limit = '')
		{
		$query = "SELECT ".trim($fields)." FROM `".$this->DB_prefix."".trim($table)."`";
			if($where != '')
			{
				$query .= " WHERE ".trim($where);
			}
			if($orderby != '')
			{
				$query .= " ORDER BY ".trim($orderby);
			}
			if($limit != '')
			{
				$query .= " LIMIT ".trim($limit);
			}
			$select = $this->query($query);
			return $select;
		}
		
		// Fetch Functions
		public function fetch_assoc($query){
			$fetch = @mysqli_fetch_assoc($query);
			return $fetch;
		}
		public function fetch_array($query){
			$fetch = @mysqli_fetch_array($query);
			return $fetch;
		}
		public function fetch_object($query){
			$fetch = @mysqli_fetch_object($query);
			return $fetch;
		}
		
		// Count the result from the requet query
		public function num_rows($query){
			$num = @mysqli_num_rows($query);
			return $num;
		}
		
		// Clean the Memory from orders
		public function free_result($query){
			$free = @mysqli_free_result($query);
			return $free;
		}
		public function delete($table, $where)
		{
		$delete = $this->query("DELETE FROM ".$this->DB_prefix."".$table." WHERE ".$where."");
			return $delete;
		}
		
		// escape string function to protect from SQL Injection
		public function escape_string($str)
		{
			$escape = mysqli_real_escape_string($this->connect,$str);
			return $escape;
		}

		// Insert To The DataBase Functions
		public function insert($table,$data)
		{
			if(is_array($data))
			{
				$fields = "`".implode("`,`", array_keys($data))."`";
				$values = implode("','",$data);
			$query = "INSERT INTO `".$this->DB_prefix.$table."` (".$fields.") VALUES ('".$values."')";
				return $this->query($query);
			}else{
				die($data.' is not an array');
				}
		}
		
		// Update Functions
		public function update($table,$fields,$where)
		{
        return $this->query("UPDATE `".$this->DB_prefix.$table."` SET ".$fields." WHERE ".$where."");
		}
		
		// ID Functions
		public function lastID()
		{
			return mysqli_insert_id($this->connect);
		}
	}
?>