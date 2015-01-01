<?php

namespace MSTS\PTSBundle\Entity;

//use

class PTSDatalog
{
	public $TransNum;
	public $SysNum;
	public $EventType;
	public $EventStart;
	public $Duration;
	public $Source;
	public $Destination;
	public $Status;
	public $Falgs;
	public $RcvId;
	public $RcvTime;
	

	function getAllResults($qryfilters) {
		// connect to database
		$mysqli = new \mysqli("localhost", "pts_logger","colombopts", "pts_datalog");
		if ($mysqli->connect_errno) {
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		// form the query
		$qry = "SELECT * FROM eventlog "; // (SELECT *, ";
		// $qry .= "(SELECT station_name FROM station S WHERE E.Source = S.station AND E.System = S.system AND E.Status < 34) as strSource, ";
		// $qry .= "(E.Source) as strSource, ";
		// $qry .= "(SELECT station_name FROM station D WHERE E.Destination = D.station AND E.System = D.system AND E.Status < 32) as strDest ";
		// $qry .= "(E.Destination) as strDest ";
		// $qry .= " FROM eventlog E) AS A ";
		
		// add WHERE clauses
		$qry_where = NULL;
		if (array_key_exists('ReceiverID',$qryfilters)) {
			$qry_where.= "ReceiverID = " . $qryfilters['ReceiverID'];
		}
		if (array_key_exists('sysnum',$qryfilters)) {
			if(strlen($qry_where)){ $qry_where .= " AND ";}
			$qry_where.= "System = " . $qryfilters['sysnum'];
		}
		if (array_key_exists('eventnum',$qryfilters)) {
			if($qryfilters['eventnum']==1) {
				if(strlen($qry_where)){ $qry_where .= " AND ";}
				$qry_where.= "EventType <= 9 ";
			} elseif ($qryfilters['eventnum']==2) {
				if(strlen($qry_where)){ $qry_where .= " AND ";}
				$qry_where.= "EventType > 9 ";
			}
		}
        if (array_key_exists('pagekey', $qryfilters)) {
			if(strlen($qry_where)){ $qry_where .= " AND ";} 
			$datefrom=date("Y-m-d H:i:s", strtotime($qryfilters['pagekey']));
			$qry_where .= "EventStart <= '". $datefrom . "'";
		} elseif (array_key_exists('datefrom',$qryfilters)) {
			if(strlen($qry_where)){ $qry_where .= " AND ";} 
			$datefrom=date("Y-m-d", strtotime($qryfilters['datefrom']));
			$qry_where .= "EventStart <= '" . $datefrom . " 23:59:59'";
		}
		//if (array_key_exists('datethrough',$qryfilters)) {
		//	if(strlen($qry_where)){ $qry_where .= " AND ";} 
		//	$datethrough=date("Y-m-d", strtotime($qryfilters['datethrough'])+86400); // add 1 day
		//	$qry_where .= "EventStart < '". $datethrough ."'";
		//}
		if (array_key_exists('srcsta',$qryfilters)) {
			if(strlen($qry_where)){ $qry_where .= " AND ";}
			$qry_where.= "MainStationName = '" . $qryfilters['srcsta'] . "'";
		}
		if (array_key_exists('deststa',$qryfilters)) {
			if(strlen($qry_where)){ $qry_where .= " AND ";}
			$qry_where.= "SubStationName = '" . $qryfilters['deststa'] . "'";
		}
		
		// add conditional to query
		if (strlen($qry_where)) {
			$qry.="WHERE " . $qry_where;
		}
		// where to include proper station names
		//$qry .= "E.Source = S.station AND E.System = S.system AND E.Destination = D.station AND E.System = D.system";
		
		// add ORDER BY clause
		$qry.= " ORDER BY EventStart DESC";

		// add LIMIT rows
		if (array_key_exists('numrows',$qryfilters)) {
			$qry.= " LIMIT ". $qryfilters['numrows'];
		} else { $qry.= " LIMIT 100"; } // must always have a limit ... for now
		
//echo $qry;
		
		// prepare sql for query
		if (!($stmt = $mysqli->prepare($qry))) {
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			echo $qry;
		}

		// execute query
		if (!$stmt->execute()) {
			 echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		}

		// capture results
		if (!($res = $stmt->get_result())) {
			echo "Getting result set failed: (" . $stmt->errno . ") " . $stmt->error;
		}
		$resall = $res->fetch_all(MYSQLI_ASSOC);
/*		
		////////////////////////////////////
		// Now get all station descriptors
		$qry = "SELECT * FROM station";
		// prepare sql for query
		if (!($stmt = $mysqli->prepare($qry))) {
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			echo $qry;
		}

		// execute query
		if (!$stmt->execute()) {
			 echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		}

		// capture results
		if (!($res = $stmt->get_result())) {
			echo "Getting result set failed: (" . $stmt->errno . ") " . $stmt->error;
		}
		while( $row=mysqli_fetch_assoc($res) ) {
			$stationName[$row['station']][$row['system']] = $row['station_name'];
		}
*/		
		// add string descriptors to event, status, and flags
		for ($i=0; $i<count($resall); $i++){
			$resall[$i]['strEvent']=$this->eventString($resall[$i]['Status']);
			$resall[$i]['strStatus']=$this->statusString($resall[$i]['Status']);
			$resall[$i]['strFlags']=$this->flagString($resall[$i]['Status'], $resall[$i]['Flags']);
			// if status <= 9 (regular transaction) then assign source/dest string names
/*			if ($resall[$i]['Status'] < 32) {
				if (isset($stationName[$resall[$i]['Source']][$resall[$i]['System']])) {
					$resall[$i]['strSource']=$stationName[$resall[$i]['Source']][$resall[$i]['System']];
				} else {
					$resall[$i]['strSource']="?";
				}
				if (isset($stationName[$resall[$i]['Destination']][$resall[$i]['System']])) {
					$resall[$i]['strDest']=$stationName[$resall[$i]['Destination']][$resall[$i]['System']];
				} else {
					$resall[$i]['strDest']="?";
				}
			} else {
				$resall[$i]['strSource']="";
				$resall[$i]['strDest']="";
			}
*/
		}
		$mysqli->close();
		return $resall;	
	}
	
	// declare lookup functions
	function flagString($StatusValue, $FlagValue) {
		$myStr = "";
		If ($StatusValue < 32)  // transaction event
		{
			If ($FlagValue == 0) {$myStr = "None;";}
			If ($FlagValue & 1) {$myStr .= "Stat; ";}
			If ($FlagValue & 2) {$myStr .= "Carrier Return; ";}
			If ($FlagValue & 4) {$myStr .= "Auto Return Set; ";}
			If ($FlagValue & 8) {$myStr .= "Door Opened; ";}
			If ($FlagValue & 16) {$myStr .= "Auto Return Performed;";}
			if ($FlagValue & 32) {$myStr .= "Secure;";}
		}
		elseif ($StatusValue == 64) // door open event
		{
			If ($FlagValue == 0) {$myStr = "None;";}
			If ($FlagValue & 1) {$myStr .= "Main station door opened; ";}
			If ($FlagValue & 2) {$myStr .= "Sub station door opened; ";}
			If ($FlagValue & 4) {$myStr .= "Extra carrier inserted;";}
		}
		return $myStr;
	}
	function statusString($StatusValue) {
		if ($StatusValue == 0) {$myStr = "Complete";}
		elseif ($StatusValue == 1) {$myStr = "Divert T/O";}
		elseif ($StatusValue == 2) {$myStr = "Depart T/O";}
		elseif ($StatusValue == 3) {$myStr = "Arrive T/O";}
		elseif ($StatusValue == 4) {$myStr = "Blower T/O";}
		elseif ($StatusValue == 5) {$myStr = "Tran Cancel";}
		elseif ($StatusValue == 6) {$myStr = "Cancel Oper";}
		elseif ($StatusValue == 7) {$myStr = "Cancel CIC";}
		elseif ($StatusValue == 8) {$myStr = "Cancel Rmt";}
		elseif ($StatusValue == 9) {$myStr = "Cant Stack";}
		elseif ($StatusValue == 64) {$myStr = "Door";}
		else {$myStr = "";}
		return $myStr;
	}
	function eventString($StatusValue) {
		if ($StatusValue == 0) {$myStr = "Transaction";}
		elseif ($StatusValue <= 9) {$myStr = "Incomplete";}
		elseif ($StatusValue == 32) {$myStr = "Secure ID";}
		elseif ($StatusValue == 33) {$myStr = "Std Scan";}
		elseif ($StatusValue == 64) {$myStr = "Door Open";}
		elseif ($StatusValue == 65) {$myStr = "Man.Purge";}
		elseif ($StatusValue == 66) {$myStr = "Aut.Purge";}
		elseif ($StatusValue == 67) {$myStr = "Sys.Reset";}
		else {$myStr = "Unknown";}
		return $myStr;
	}
	function stationString($station, $system) {
		$myQry = mysql_query("SELECT station_name FROM station WHERE system = ".$system." AND station = ".$station.";");
		if ($myQry) { $myRow = mysql_fetch_array($myQry);
					   return $myRow['station_name'];}
		else 		  {return '-';}
	}
	
	public function getUserRecords($qryfilters) {
		// connect to database
		$mysqli = new \mysqli("localhost", "pts_logger","colombopts", "pts_datalog");
		if ($mysqli->connect_errno) {
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		// form the query
		$qry = "SELECT *, (SELECT MAX(EventStart) FROM eventlog WHERE users.CardNum=eventlog.ReceiverID) AS LastEvent FROM users ";
		//$qry = "SELECT *, ('2012-01-02') AS LastEvent FROM users ";
//var_dump($qryfilters);
		
		// add WHERE clauses
		$qry_where = NULL;
		if (array_key_exists('recno',$qryfilters)) {
			$qry_where.= "RecNo = " . $qryfilters['recno'];
		}
        // This case for tube system controller to request next block
		if (array_key_exists('recnos',$qryfilters)) {
     		if(strlen($qry_where)){ $qry_where .= " AND ";}
			$qry_where.= "RecNo > " . $qryfilters['recnos'];
		}        
		if (array_key_exists('cardnum',$qryfilters)) {
			$qry_where.= "CardNum = " . $qryfilters['cardnum'];
		}
		if (array_key_exists('active',$qryfilters)) {
     		if(strlen($qry_where)){ $qry_where .= " AND ";}
			$qry_where.= "Active = ";
			if ($qryfilters['active'] == "an") { $qry_where.= "'N' "; }
			else { $qry_where.= "'Y' "; }
		}
		if (array_key_exists('statusdate',$qryfilters)) {
     		if(strlen($qry_where)){ $qry_where .= " AND ";}
			$qry_where.= "StatusDate >= '" . $qryfilters['statusdate'] . "'";
		}

		// add conditional to query
		if (strlen($qry_where)) {
			$qry.="WHERE " . $qry_where;
		}
		
		// add ORDER BY clause
		if (array_key_exists('sort',$qryfilters)) {
			$qry.= " ORDER BY "; 
			if ($qryfilters['sort'] == "fn") { $qry.= "FName ASC"; }
			elseif ($qryfilters['sort'] == "an") { $qry.= "IDNum ASC"; }
			elseif ($qryfilters['sort'] == "bn") { $qry.= "CardNum ASC"; }
			elseif ($qryfilters['sort'] == "la") { $qry.= "LastEvent DESC, LName ASC";}
			elseif ($qryfilters['sort'] == "rn") { $qry.= "RecNo ASC"; }
			elseif ($qryfilters['sort'] == "arn") { $qry.= "Active DESC, RecNo ASC"; }
			else                                 { $qry.= "LName ASC"; }
		} else {
			$qry.= " ORDER BY LName ASC";
		}

			// add LIMIT rows
		if (array_key_exists('numrows',$qryfilters)) {
			$qry.= " LIMIT ". $qryfilters['numrows'];
		}
//echo var_dump($qry);		
		// prepare sql for query
		if (!($stmt = $mysqli->prepare($qry))) {
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			echo $qry;
		}
		// execute query
		if (!$stmt->execute()) {
			 echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		}

		// capture results
		if (!($res = $stmt->get_result())) {
			echo "Getting result set failed: (" . $stmt->errno . ") " . $stmt->error;
		}
		$resall = $res->fetch_all(MYSQLI_ASSOC);
		$mysqli->close();
		return $resall;	
	}
	
	public function addUserRecord($qryfilters) {
		// adds or updates the database
		// if RecNo = 0 or NULL then add new, otherwise update existing
		
		// connect to database
		$mysqli = new \mysqli("localhost", "pts_logger","colombopts", "pts_datalog");
		if ($mysqli->connect_errno) {
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		// echo var_dump($qryfilters);
		if (array_key_exists('RecNo',$qryfilters) && ($qryfilters['RecNo'] > 0)) 
		{
				// Update existing record
				$qry = 'UPDATE users SET ';
				$qry .= 'LName = "' . $qryfilters['LName'].'", ';
				$qry .= 'FName = "' . $qryfilters['FName'].'", ';
				$qry .= 'IDNum = "' . $qryfilters['IDNum'].'", ';
				$qry .= 'CardNum = ' . $qryfilters['CardNum'].', ';
				$qry .= 'Active = "' . $qryfilters['Active'].'" ';
					// $qry .= $qryfilters['Active'] ? '"Y" ' : '"N" ';
					
				$qry .= 'WHERE RecNo = ' . $qryfilters['RecNo'];
		} else
		{
			// Insert new record
			$qry = 'INSERT INTO users (LName, FName, IDNum, CardNum, Active) ';
			$qry .= 'VALUES ("' . $qryfilters['LName'].'"';
			$qry .= ',"' . $qryfilters['FName'].'"';
			$qry .= ',"' . $qryfilters['IDNum'].'"';
			$qry .= ',' . $qryfilters['CardNum'];
			$qry .= ',"' . $qryfilters['Active'].'")';
		}
		// echo var_dump($qry);
		// prepare sql for query
		if (!($stmt = $mysqli->prepare($qry))) {
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			echo $qry;
		}

		// execute query
		if (!$stmt->execute()) {
			 echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		}
		$mysqli->close();
		return $stmt;
	}
    public function countUserRecords($qryfilters) {
        // count how many active users
		// connect to database
		$mysqli = new \mysqli("localhost", "pts_logger","colombopts", "pts_datalog");
		if ($mysqli->connect_errno) {
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		// form the query
		$qry = "SELECT COUNT(*) FROM users WHERE Active = 'Y'";
        
		// prepare sql for query
		if (!($stmt = $mysqli->prepare($qry))) {
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			echo $qry;
		}
		// execute query
		if (!$stmt->execute()) {
			 echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		}

		// capture results
		if (!($res = $stmt->get_result())) {
			echo "Getting result set failed: (" . $stmt->errno . ") " . $stmt->error;
		}
		$resall = $res->fetch_row();

		return $resall;	
    }
    public function getLastContTrans($sysnum)
    {   // return the parameter for last contiguous transaction number
        // connect to database
        $mysqli = new \mysqli("localhost", "pts_logger","colombopts", "pts_datalog");
        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        }
        // form the query
        $qry = "SELECT ParVal2 FROM parameters WHERE ParName = 'LastContTrans' AND ParVal1 = ".$sysnum;
        
        // prepare sql for query
        if (!($stmt = $mysqli->prepare($qry))) {
            echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
            echo $qry;
        }
        // execute query
        if (!$stmt->execute()) {
             echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        // capture results
        if (!($res = $stmt->get_result())) {
            echo "Getting result set failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        $resall = $res->fetch_row();
        return $resall[0];
    }
}
?>
