<?php

declare(strict_types=1);

require_once __DIR__  . '/../libs/helper/BufferHelper.php';
require_once __DIR__  . '/../libs/helper/DebugHelper.php';

trait Database
{
    private $isConnected = false;

    protected function Login()
    {
      if ($this->ReadPropertyString('Host') == '') {
       return false;
      }
	  if ($this->ReadPropertyString('Database') == '') {
       return false;
      }
	  if ($this->ReadPropertyString('Username') <> '' and  $this->ReadPropertyString('Password') <> '') {
       try {
			$serverName = $this->ReadPropertyString('Host');
            $database = $this->ReadPropertyString('Database');
			$Username = $this->ReadPropertyString('Username');
			$Password = $this->ReadPropertyString('Password');
			$ParentId = $this->ReadPropertyInteger('ParentId');
			$conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $Username, $Password);   
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}
		catch( PDOException $e ) {
              return false;
		}	 
		return true;
      }
	  if ($this->ReadPropertyString('Username') == '' and  $this->ReadPropertyString('Password') == '') {
		try {
			$serverName = $this->ReadPropertyString('Host');
            $database = $this->ReadPropertyString('Database');
			$conn = new PDO( "sqlsrv:server=$serverName;Database=$database", NULL, NULL);   
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}
		catch( PDOException $e ) {
              return false;
		}	 
		return true;
	  }
    }
	
    protected function CreateDB()
    {
		try {
            $serverName = "ANDREASPC\SQLEXPRESS";
            $conn = new PDO( "sqlsrv:server=$serverName;Database = master", NULL, NULL);   
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$database = $this->ReadPropertyString('Database');
            $query = 'CREATE DATABASE ' . $database . '';
	        $result = $conn->query( $query );
            }
            catch( PDOException $err ) {
              return false;
            }
        return true;
    }

    protected function SelectDB()
    {
        if ($this->isConnected) {
            //return $this->DB->select_db($this->ReadPropertyString('Database'));
        }
        return true;
    }

    protected function Logout()
    {
        if ($this->isConnected) {
            $serverName = $this->ReadPropertyString('Host');
            $database = $this->ReadPropertyString('Database');
			$conn = new PDO( "sqlsrv:server=$serverName;Database = $database", NULL, NULL);   
			$conn = NULL;
        }
        return false;
    }

    protected function TableExist()
    {
        $serverName = $this->ReadPropertyString('Host');
        $database = $this->ReadPropertyString('Database');
		if ($this->ReadPropertyString('Table') == '') {
	       echo $this->Translate('Table has no name.');
           return false;
        }
		if ($this->ReadPropertyInteger('ParentId') == 0) {
	       echo $this->Translate('ParentId has no name.');
           return false;
        }
	
		$table = $this->ReadPropertyString('Table');	
		$conn = new PDO( "sqlsrv:server=$serverName;Database = $database", NULL, NULL);   
		$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		$query = 'CREATE TABLE [dbo].[' . $table . '](
			[Id] [int] IDENTITY(1,1) PRIMARY KEY,
			[ParentId] [int] NULL,
			[ChildId] [int] NULL,
			[KeyValue] [nvarchar](40) NULL,
			[Description] [nvarchar](max) NULL,
			[Value] [nvarchar](max) NULL,
			[Unit] [nvarchar](20) NULL,
			[Typ] [nvarchar](20) NULL,
			[LastUpdate] [datetime] NULL)';
			
		try {
			 $stmt = $conn->query( $query );
			}
		catch( PDOException $err ) {
		    $codeNr = $err->getCode();
			if ($codeNr == '42S01') {
			return true;
			}
			return false;
		}  
	    return true;  
    }
	
	protected function TableExists($VarId)
    {
		
    }

    protected function CreateAddToTable($VarId, $VarTyp, $Description, $Value, $Unit)
    {
		$table = $this->ReadPropertyString('Table');		
		$query = 'SELECT ChildId FROM ['.$table.'] WHERE (ChildId = '.$VarId.')';
		try {
			 $serverName = $this->ReadPropertyString('Host');
			 $database = $this->ReadPropertyString('Database');
			 $conn = new PDO( "sqlsrv:server=$serverName;Database = $database", NULL, NULL);   
			 $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			 $stmt = $conn->query($query);
			 $result = $stmt->fetch(PDO::FETCH_ASSOC);
			 if ($result <> '') {return;}
			 print_r($result);
			}
		catch( PDOException $err ) {
			echo $err;
		    return false;
		}  	
		
        switch ($VarTyp) {
            case VARIABLETYPE_INTEGER:
                $Typ = 'INT';
				$SqlValue = $Value;
                break;
            case VARIABLETYPE_FLOAT:
                $Typ = 'REAL';
				$Value = strval($Value);
				$Value = iconv('UTF-8', 'UTF-16LE', $Value); //convert into native encoding 
		        $Value = bin2hex($Value); //convert into hexadecimal
				$SqlValue = 'CONVERT(nvarchar(MAX), 0x'.$Value.')';
                break;
            case VARIABLETYPE_BOOLEAN:
                $Typ = 'BIT';
				$Value = $Value ? 'true' : 'false';
				$Value = strval($Value);
				$Value = iconv('UTF-8', 'UTF-16LE', $Value); //convert into native encoding 
		        $Value = bin2hex($Value); //convert into hexadecimal
				$SqlValue = 'CONVERT(nvarchar(MAX), 0x'.$Value.')';
                break;
            case VARIABLETYPE_STRING:
                $Typ = 'STRING';
				$Value = iconv('UTF-8', 'UTF-16LE', $Value); //convert into native encoding 
		        $Value = bin2hex($Value); //convert into hexadecimal
				$SqlValue = 'CONVERT(nvarchar(MAX), 0x'.$Value.')';
                break;
        }
		$ParentId = $this->ReadPropertyInteger('ParentId');
		
		$VarName = IPS_GetName($VarId);
		$VarName = iconv('UTF-8', 'UTF-16LE', $VarName); //convert into native encoding 
		$VarName = bin2hex($VarName); //convert into hexadecimal
		
		$Description = iconv('UTF-8', 'UTF-16LE', $Description); //convert into native encoding 
	    $Description = bin2hex($Description); //convert into hexadecimal
		
		$Unit = iconv('UTF-8', 'UTF-16LE', $Unit); //convert into native encoding 
		$Unit = bin2hex($Unit); //convert into hexadecimal
		
		$Typ = iconv('UTF-8', 'UTF-16LE', $Typ); //convert into native encoding 
		$Typ = bin2hex($Typ); //convert into hexadecimal
		
		
		
		$query = 'INSERT INTO ['.$table.'] (ParentId,ChildId,KeyValue,Description,Value,Unit,Typ,LastUpdate) 
				  VALUES('.$ParentId.',
				  '.$VarId.',
				  CONVERT(nvarchar(MAX), 0x'.$VarName.'),
				  CONVERT(nvarchar(MAX), 0x'.$Description.'),
				  '.$SqlValue.',
				  CONVERT(nvarchar(MAX), 0x'.$Unit.'),
				  CONVERT(nvarchar(MAX), 0x'.$Typ.'),
				  GETDATE())';
		try {
			 $stmt = $conn->query( $query );
			}
		catch( PDOException $err ) {
			echo $err;
		    return false;
		}  
		return true;
    }

    protected function RenameTable($OldVariableID, $NewVariableID)
    {
        if (!$this->isConnected) {
            return false;
        }

        $query = 'RENAME TABLE ' . $this->ReadPropertyString('Database') . '.var' . $OldVariableID . ' TO ' . $this->ReadPropertyString('Database') . '.var' . $NewVariableID . ';';
        $result = $conn->query( $query );
        $this->SendDebug('RenameTable', $result, 0);
        return $result;
    }

    protected function DeleteData($VariableID, $Startzeit, $Endzeit)
    {
        if (!$this->isConnected) {
            return false;
        }

        $query = 'DELETE FROM var' . $VariableID . ' WHERE ((timestamp >= from_unixtime(' . $Startzeit . ')) and (timestamp <= from_unixtime(' . $Endzeit . ')));';
        /* @var $result mysqli_result */
        $result = $conn->query( $query );
        if ($result) {
            //$result = $this->DB->affected_rows;
        }
        return $result;
    }

    protected function GetLoggedData($VariableID, $Startzeit, $Endzeit, $Limit)
    {
        if (!$this->isConnected) {
            return false;
        }

        $query = "SELECT  unix_timestamp(timestamp) AS 'TimeStamp', value AS 'Value' " .
                'FROM  var' . $VariableID . ' ' .
                'WHERE  ((timestamp >= from_unixtime(' . $Startzeit . ')) ' .
                'and (timestamp <= from_unixtime(' . $Endzeit . '))) ' .
                'ORDER BY timestamp DESC ' .
                'LIMIT ' . $Limit;
        /* @var $result mysqli_result */
        $result = $conn->query( $query );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    protected function GetLoggedDataTyp($VariableID)
    {
        $query = 'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS ' .
                "WHERE ((TABLE_NAME = 'var" . $VariableID . "') AND (COLUMN_NAME = 'value'))";

        $sqlresult = $conn->query( $query );
        switch (strtolower($sqlresult->fetch_row()[0])) {
            case 'double':
            case 'real':
                return VARIABLETYPE_FLOAT;
            case 'int':
                return VARIABLETYPE_INTEGER;
            case 'bit':
                return VARIABLETYPE_BOOLEAN;
            default:
                return VARIABLETYPE_STRING;
        }
    }

    protected function GetAggregatedData($VariableID, $Typ, $Startzeit, $Endzeit, $Limit)
    {
        switch ($Typ) {
            case 0:
                $Time = 10000;
                $Half = 3000;
                break;
            case 1:
                // YYMMDDhhmmss
                $Time = 1000000;
                $Half = 120000;
                break;
            case 2:
                // YYMMDDhhmmss
                $Time = 7000000;
                $Half = 350000;
                break;
            case 3:
                //    YYMMDDhhmmss
                $Time = 100000000;
                $Half = 15000000;
                break;
            case 4:
                //     YYMMDDhhmmss
                $Time = 10000000000;
                $Half = 600000000;
                break;
            case 5: //5 min
                $Time = 500;
                $Half = 230;
                break;
            case 6: //1 min
                $Time = 100;
                $Half = 30;
                break;
        }
        $query = "SELECT MIN(value) AS 'Min', MAX(value) AS 'Max', AVG(value) AS 'Avg', " .
                'UNIX_TIMESTAMP(convert((min(timestamp) div ' . $Time . ')*' . $Time . ' + ' . $Half . ', datetime)) ' .
                "as 'TimeStamp' FROM var" . $VariableID . ' ' .
                'WHERE timestamp BETWEEN from_unixtime(' . $Startzeit . ') ' .
                'AND from_unixtime(' . $Endzeit . ') GROUP BY timestamp div ' . $Time . ' ' .
                "ORDER BY 'TimeStamp' DESC " .
                'LIMIT ' . $Limit;
        /* @var $result mysqli_result */
        $result = $conn->query( $query );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    protected function GetVariableTables()
    {
        if (!$this->isConnected) {
            return [];
        }
        $query = "SELECT right(TABLE_NAME,5) as 'VariableID' FROM information_schema.TABLES WHERE table_schema = '" . $this->ReadPropertyString('Database') . "' ORDER BY 'VariableID' ASC";
        $sqlresult = $conn->query( $query );
        if ($sqlresult === false) {
            return [];
        }
        $Result = $sqlresult->fetch_all(MYSQLI_ASSOC);
        foreach ($Result as &$Item) {
            $Item['VariableID'] = (int) $Item['VariableID'];
        }
        return $Result;
    }

    protected function GetSummary($VariableId)
    {
        if (!$this->isConnected) {
            return false;
        }
		$serverName = $this->ReadPropertyString('Host');
	    $database = $this->ReadPropertyString('Database');
		$table = $this->ReadPropertyString('Table');		
		$query = 'SELECT LastUpdate FROM '.$table.' WHERE (ChildId = '.$VariableId.') ORDER BY LastUpdate';
		try {	 
			 $conn = new PDO( "sqlsrv:server=$serverName;Database = $database", NULL, NULL);   
			 $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			 $stmt = $conn->query($query);
			 $result = $stmt->fetch(PDO::FETCH_ASSOC);
			 if ($result <> '') {return;}
			 print_r($result);
			}
		catch( PDOException $err ) {
			echo $err;
		    return false;
		}  	

        return;
        /* @var $sqlresult mysqli_result */

        $sqlresult = $conn->query( $query );
        $Result['FirstTimestamp'] = (int) $sqlresult->fetch_row()[0];

        $query = "SELECT unix_timestamp(timestamp) AS 'TimeStamp' " .
                'FROM  var' . $VariableId . ' ' .
                'ORDER BY timestamp DESC ' .
                'LIMIT 1';
        /* @var $sqlresult mysqli_result */
        $sqlresult = $conn->query( $query );
        $Result['LastTimestamp'] = (int) $sqlresult->fetch_row()[0];

        $query = "SELECT count(*) AS 'Count' " .
                'FROM  var' . $VariableId . ' ';
        /* @var $sqlresult mysqli_result */
        $sqlresult = $conn->query( $query );
        $Result['Count'] = (int) $sqlresult->fetch_row()[0];

        $query = "SELECT count(*) AS 'Count' " .
                'FROM  var' . $VariableId . ' ';
        /* @var $sqlresult mysqli_result */
        $sqlresult = $conn->query( $query );
        $Result['Count'] = (int) $sqlresult->fetch_row()[0];

        $query = "SELECT data_length AS 'Size' " .
                'FROM information_schema.TABLES ' .
                "WHERE table_schema = '" . $this->ReadPropertyString('Database') . "' " .
                "AND table_name = 'var" . $VariableId . "' ";
        /* @var $sqlresult mysqli_result */
        $sqlresult = $conn->query( $query );
        $Result['Size'] = (int) $sqlresult->fetch_row()[0];
        return $Result;
    }

    protected function WriteValue($Variable, $NewValue, $HasChanged, $Timestamp)
    {
		
        if ($HasChanged) {
			
		$table = $this->ReadPropertyString('Table');		
		$query = 'SELECT Id,ParentId,ChildId,KeyValue,Description,Value,Unit,Typ,LastUpdate FROM ['.$table.'] WHERE (ChildId = '.$Variable.')';
		try {
			 $serverName = $this->ReadPropertyString('Host');
			 $database = $this->ReadPropertyString('Database');
			 $conn = new PDO( "sqlsrv:server=$serverName;Database = $database", NULL, NULL);   
			 $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			 $stmt = $conn->query($query);
			 $result = $stmt->fetch(PDO::FETCH_NAMED);
			}
		catch( PDOException $err ) {
			//echo $err;
			echo $this->Translate('Table not exists.');
		    return false;
		}  	
		    $Typ = $result['Typ'];
			$Description = $result['Description'];
			$Unit = $result['Unit'];
			
			
			//print_r($result);
		
		
			$Value = $NewValue;
			switch ($Typ) {
            case 'INT':
                $Typ = 'INT';
				$SqlValue = $Value;
                break;
            case 'REAL':
                $Typ = 'REAL';
				$Value = strval($Value);
				$Value = iconv('UTF-8', 'UTF-16LE', $Value); //convert into native encoding 
		        $Value = bin2hex($Value); //convert into hexadecimal
				$SqlValue = 'CONVERT(nvarchar(MAX), 0x'.$Value.')';
                break;
            case 'BIT':
                $Typ = 'BIT';
				$Value = $Value ? 'true' : 'false';
				$Value = strval($Value);
				$Value = iconv('UTF-8', 'UTF-16LE', $Value); //convert into native encoding 
		        $Value = bin2hex($Value); //convert into hexadecimal
				$SqlValue = 'CONVERT(nvarchar(MAX), 0x'.$Value.')';
                break;
            case 'STRING':
                $Typ = 'STRING';
				$Value = iconv('UTF-8', 'UTF-16LE', $Value); //convert into native encoding 
		        $Value = bin2hex($Value); //convert into hexadecimal
				$SqlValue = 'CONVERT(nvarchar(MAX), 0x'.$Value.')';
                break;
        }
		$ParentId = $this->ReadPropertyInteger('ParentId');
		
		$VarName = IPS_GetName($Variable);
		$VarName = iconv('UTF-8', 'UTF-16LE', $VarName); //convert into native encoding 
		$VarName = bin2hex($VarName); //convert into hexadecimal
		
		$Description = iconv('UTF-8', 'UTF-16LE', $Description); //convert into native encoding 
	    $Description = bin2hex($Description); //convert into hexadecimal
		
		$Unit = iconv('UTF-8', 'UTF-16LE', $Unit); //convert into native encoding 
		$Unit = bin2hex($Unit); //convert into hexadecimal
		
		$Typ = iconv('UTF-8', 'UTF-16LE', $Typ); //convert into native encoding 
		$Typ = bin2hex($Typ); //convert into hexadecimal
		
		
		
		$query = 'INSERT INTO ['.$table.'] (ParentId,ChildId,KeyValue,Description,Value,Unit,Typ,LastUpdate) 
				  VALUES('.$ParentId.',
				  '.$Variable.',
				  CONVERT(nvarchar(MAX), 0x'.$VarName.'),
				  CONVERT(nvarchar(MAX), 0x'.$Description.'),
				  '.$SqlValue.',
				  CONVERT(nvarchar(MAX), 0x'.$Unit.'),
				  CONVERT(nvarchar(MAX), 0x'.$Typ.'),
				  GETDATE())';
		try {
			 $stmt = $conn->query( $query );
			}
		catch( PDOException $err ) {
			echo $err;
		    return false;
		}  
		return true;
			
		}
}

protected function UnregisterVariableWatch($VarId)
    {
        if ($VarId == 0) {
            return;
        }

        $this->SendDebug('UnregisterVM', $VarId, 0);
        $this->UnregisterMessage($VarId, VM_DELETE);
        $this->UnregisterMessage($VarId, VM_UPDATE);
        $this->UnregisterReference($VarId);
    }

    /**
     * Registriert eine Überwachung einer Variable.
     *
     * @param int $VarId IPS-ID der Variable.
     */
    protected function RegisterVariableWatch(int $VarId)
    {
        if ($VarId == 0) {
            return;
        }
        $this->SendDebug('RegisterVM', $VarId, 0);
        $this->RegisterReference($VarId);
        $this->RegisterMessage($VarId, VM_DELETE);
        $this->RegisterMessage($VarId, VM_UPDATE);
    }
}

/* @} */
