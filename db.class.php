<?php
/**************************************************
* A Database Connection Class using the PHP MySQLi API
* 
* Developed by Brennan Murphy
* (c) 22lbs Media - www.22lbs.ca
*
* This class is used to connect to a MySQL database and uses the MySQLi API introducted in PHP5. 
* It has been inspired by WordPress' wpdb class
*
* Function examples are inline. If used properly you should never have to call a mysqli function directly in your application.
* Activate by including this file in your php files and start the connection by creating a new class object. Example:
* 				$db = new DB();
* MAKE SURE TO EDIT THE DATABASE VAULES ON LINES 23-26
*
* Feel free to modify and redistribule. All I ask is you give credit.
**************************************************/

class DB {
	/*******************
	* Edit the values below with your server configuration details.
	*******************/
	private $db_name = DB_NAME;
	private $db_user = DB_USER;
	private $db_pass = DB_PASSWORD;
	private $db_host = DB_HOST;
	
	/* #### NO NEED TO EDIT BELOW THIS LINE #### */
	
	/*******************
	* The lastest mysqli result object
	******************/
	public $result;
	
	/*******************
	* The value of the last sql query. Good for debugging.
	*******************/
	public $last_query;
	
	/*******************
	* The number of row affected by the last sql operation
	*******************/
	public $num_rows;
	
	/*******************
	* The auto incriment id affected by the last sql operation
 	*******************/
	public $last_id;
	
	protected $mysqli;
	protected $select	= 'SELECT * FROM ';
	protected $where	= ' WHERE ';
	protected $limit	= ' LIMIT ';
	protected $like		= ' LIKE ';
	protected $sort		= ' ORDER BY ';
	
	public function __construct(){
	
		// connect to MySQL and select database
		$this->mysqli = new mysqli( $this->db_host , $this->db_user , $this->db_pass , $this->db_name );
		
		if( mysqli_connect_errno() ){
			throw new Exception( 'Error connecting to MySQL: ' . $this->mysqli->error );
		}
		
	}
	
	/*******************
	* Use to escape data before entering into one of $this->fetch(), $this->query,() or $this->delete(). Replacment for mysql_real_escape_string
	* NOTE: data going into $this->insert() or $this->update() is escaped by the function
	* Example:  $db->escape("Some value's");
	*******************/
	public function escape( $string ) {
		return $this->mysqli->real_escape_string($string);
	}
	
	/*******************
	* Use for running a sql query. Only use for complicated querys. The result is stored in $this->result. 
	* Note: You must escape data before inserting into this function. Use $this->escape() 
	* Example:  $db->query("SELECT `field1` as name, `field2` as friend FROM `table` WHERE email LIKE '%@gmail.com' ORDER BY name ASC");
	*******************/
	public function query( $query ) {
	
		if( !$this->result = $this->mysqli->query( $query ) ){
			throw new Exception( 'Error running SQL query: ' . $this->mysqli->error . '<br /><br />' . $query );
			return false;
		} else {
			$this->num_rows = $this->mysqli->affected_rows;
			$this->last_query = $query;
			$this->last_id = $this->mysqli->insert_id;
			return true;
		}
		
	}
	
	/*******************
	* The most common function. Used to retive a result set from the database. 
	* Specify the table name in the first field, the where clause in the second, an array of what colums you want to return in the third. If the third field is false, or undefined, will result all columns.
	* Example: $db->fetch( 'table_name' , "email LIKE '%gmail%'" , array('id','name','email') );
	*******************/
	public function fetch($table='default_table' , $where=false , $cols=false , $sort=false ) {
		$sql = 'SELECT ';
		if ($cols) {
			$sql .='`' . implode( "`,`" , array_values($cols) ) . '`';
		} else {
			$sql .= '*';
		} 
		
		$sql .= ' FROM `' . $table . '`';
		
		if ($where) {
			$sql .= $this->where . $where;
		}
		
		if ($sort) {
			$sql .= $this->sort . $sort;
		}
		
		$this->query($sql);
		
		return TRUE;
		
	}
	
	/*******************
	* Returns the next row from the last query. Need to run $this->query or $this->fetch before using. Replacement for mysql_fetch_assoc
	* Example:  $row = $db->fetchRow();
	*******************/
	public function fetchRow(){
	
		while( $row = $this->result->fetch_assoc() ) {
			if (isset($row['id'])) {
			$this->last_id = $row['id'];
			}
			return $row;
			
		}
		return false;
		
	}
	
	/*******************
	* Returns all the rows from the last query in a array of assocative arrays. Need to run $this->query or $this->fetch before using.
	* Example:  $result = $db->fetchAll();
	*******************/
	public function fetchAll() {
	
		$this->result->data_seek(0);
	
		$rows = array();
		while( $row = $this->fetchRow()){
			$rows[] = $row;
		}
		return $rows;
		
	}
	
	/*******************
	* Returns a count of the number of rows in a table. If you specify the second parameter, the specified where clause will be applied. 
	* Only use this if you won't need to use the result set, otherwise use a combo of $this->fetch() and $this->num_rows
	* Example:  $db->count( 'table_name' , "email LIKE '%gmail%'" );
	*******************/
	public function count( $table='default_table' , $where=false ) {
		$sql = "SELECT COUNT(id) as count FROM `" . $table . "`";
		
		if ($where) {
			$sql .= $this->where . $where;
		}
		
		$this->query($sql);
		return $this->result->fetch_object()->count;
	}
	
	// insert row
	public function insert( $data=array() , $table='default_table' ) {
	
		$columns = "";
		$values = "";

		if ( is_array($data[0]) ) {
			foreach ( $data[0] as $column => $value ) {
				$columns .= ($columns == "") ? "`" : "`,`";
				$columns .= $column;
			}
			$columns .= "`";

			foreach ( $data as $row ) {
				foreach ( $row as $column => $value ) {
					$values .= ($values == "") ? "('" : "'";
					$values .= $this->escape($value);
					$values .= "',";
				}
				$values = substr($values, 0, -1);
				$values .= "), (";
			}
			$values = substr($values, 0, -3);

			$sql = "INSERT INTO `" . $table . "` (" . $columns . ") VALUES " . $values;

		} else {
			foreach ($data as $column => $value) {
				$columns .= ($columns == "") ? "`" : "`,`";
				$columns .= $column;
				$values .= ($values == "") ? "'" : "','";
				$values .= $this->escape($value);
			}
			$columns .= "`";
			$values .= "'";

			$sql = "INSERT INTO `" . $table . "` (" . $columns . ") VALUES (" . $values . ")";

		}
		
		if ( $this->query($sql) ) {
			$this->last_id = $this->mysqli->insert_id;
			return $this->last_id;
		} else {
			return false;
		}
		
	}
	
	// update row
	public function update($data=array(),$where='',$table='default_table') {
	
		$args=array();
		foreach($data as $field=>$value){
			$args[]="`".$field.'`="'.$this->escape($value).'"';
		}
		
		$sql = 'UPDATE ' . $table . ' SET '. implode(',',$args) . $this->where . $where;
		
		return $this->query($sql);
		
	}
	
	// delete row(s)
	public function delete($where='',$table='default_table'){
		$sql=!$where?'DELETE FROM '.$table:'DELETE FROM '.$table.' WHERE '.$where;
		$this->query($sql);
	}
}