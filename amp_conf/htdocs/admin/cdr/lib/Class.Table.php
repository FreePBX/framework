<?php

class Table {

	var $fields = "*"; // "id", "name", etc..
	var $table  = "";
	var $errstr = "";
	var $debug_st = 0;



	/* CONSTRUCTOR */

	function Table ($table =null, $liste_fields =null) {     
		
		  $this -> table = $table;
		  $this -> fields = $liste_fields;

	}



	/* MODIFY PROPRIETY*/

	function Define_fields ($liste_fields ) {     
		
          $this -> fields = $liste_fields;
	}


	function Define_table ($table) {     
		
          $this -> table = $table;
	}
	




	function Get_list ($clause=null, $order=null, $sens=null, $field_order_letter=null, $letters = null, $limite=null, $current_record=NULL) {
		//global $link;
		global $DBHandle;


		$sql = 'SELECT '.$this -> fields.' FROM '.trim($this -> table);

		
		$sql_clause='';
		if ($clause!='') 
			$sql_clause=' WHERE '.$clause;

		
		$sqlletters = "";
		if (!is_null ($letters) && (ereg("^[A-Za-z]+$", $letters)) && !is_null ($field_order_letter) && ($field_order_letter!='') ){
			$sql_letters= ' (".$field_order_letter." LIKE \''.strtolower($letters).'%\') ';

			if ($sql_clause != ""){
				$sql_clause .= " AND ";
			}else{
				$sql_clause .= " WHERE ";
			}
		}

		
		$sql_orderby = '';
		if (  !is_null ($order) && ($order!='') && !is_null ($sens) && ($sens!='') ){
			$sql_orderby = " ORDER BY $order $sens";
		}

		if (!is_null ($limite) && (is_numeric($limite)) && !is_null ($current_record) && (is_numeric($current_record)) ){
			if (DB_TYPE == "postgres"){				
				$sql_limit = " LIMIT $limite OFFSET $current_record";				
			}else{
				$sql_limit = " LIMIT $current_record,$limite";			
			}
		}

		$QUERY = $sql.$sql_clause.$sql_orderby.$sql_limit;		
		if ($this -> debug_st) echo "<br>QUERY:".$QUERY;
				
		//$res=DbExec($link, $QUERY);
		$res = $DBHandle -> query($QUERY);
  	if(DB::isError($res))
    {
			// don't know if this is the correct response but it should keep
			// #2829 from happening
			return 0;
		}

		$num = $res -> numRows();
		
		
		if ($num==0) return 0;


		for($i=0;$i<$num;$i++)
			{
				//$row[]=DbFetch($res, $i);
				$row [] =$res -> fetchRow();
			        //	print_r ($row);
			}

		
		return ($row);
	}



	function Table_count ($clause=null) {
		//global $link;
		global $DBHandle;


		$sql = 'SELECT count(*) FROM '.trim($this -> table);



		$sql_clause='';
		if ($clause!='') 
			$sql_clause=' WHERE '.$clause;

		
		$QUERY = $sql.$sql_clause;
		//echo $sql;
		
		if ($this -> debug_st) echo "<br>QUERY:".$QUERY;
		//$res=DbExec($link, $QUERY);		
		$res = $DBHandle -> query($QUERY);

		/////$num=DbCount($res);

		//$row=DbFetch($res, $i);		
		$row =$res -> fetchRow();
		//print_r ($DBHandle -> Record);
			
		//return ($row[0]);
		//echo "COUNT  : ".$row['0']."<br>";
		return ($row['0']);
	}




	function Add_table ($value, $func_fields = null, $func_table = null, $id_name = null) {
		//global $link;
		global $DBHandle;

		if ($func_fields!=""){		
			$this -> fields = $func_fields;
		}

		if ($func_table !=""){		
			$this -> table = $func_table;
		}


		$QUERY = "INSERT INTO \"".$this -> table."\" (".$this -> fields.") values (".trim ($value).")";
		if ($this -> debug_st) echo "<br>QUERY:".$QUERY;

		$res = $DBHandle -> query($QUERY);

		if (DB::isError($res)){
		//if (! $res=DbExec($link, $QUERY)) {
			//$this -> errstr = "Could not create a new instance in the table '".$this -> table."'";				
		        $this -> errstr = $DBHandle -> getMessage();																			

			
			return (false);
		}

		if ($id_name!=""){

				$oid = pg_getlastoid($res);
				if ($oid < 0) return (false);	
				

				$sql = 'SELECT "'.$id_name.'" FROM "'.$this -> table.'" WHERE oid=\''.$oid.'\'';

				if (! $res = $DBHandle -> query($sql)) return (false);
				
				$row [] =$res -> fetchRow();

				return $row[0][0]; 
		}

		
		return (true);
	}

	function Update_table ($param_update, $clause, $func_table = null) {
		//global $link;
		global $DBHandle;

		if ($param_update=="" || $clause==""){
			echo "<br>Update parameters wasn't correctly defined.<br>Check the function call 'Update_table'.";
			return (false);
		}

		if ($func_table !=""){		
			$this -> table = $func_table;
		}


		$QUERY = "UPDATE \"".$this -> table."\" SET ".trim ($param_update)." WHERE ".trim ($clause);		
		if ($this -> debug_st) echo "<br>QUERY:".$QUERY;

		
		if (! $res = $DBHandle -> query($QUERY)){
		//if (! $res=DbExec($link, $QUERY)) {
			$this -> errstr = "Could not update the instances of the table '".$this -> table."'";		
			return (false);
		}
		
		return (true);
	}



	function Delete_table ($clause, $func_table = null) {
		//global $link;
		global $DBHandle;

		if ($clause==""){
			echo "<br>Delete parameters wasn't correctly defined.<br>Check the function call 'Update_table'.";
			return (false);
		}

		if ($func_table !=""){		
			$this -> table = $func_table;
		}

		
		$QUERY = "DELETE FROM \"".$this -> table."\" WHERE (".trim ($clause).")";
		if ($this -> debug_st) echo "<br>QUERY:".$QUERY;
		
		if (! $res = $DBHandle -> query($QUERY)){
		//if (! $res=DbExec($link, $QUERY)) {
			$this -> errstr = "Could not delete the instances of the table '".$this -> table."'";		
			return (false);
		}
		
		return (true);

	}	

};

?>
