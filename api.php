<?php	
	function getRandomKey($length)
    {
        $default_arr = array("0123456789",
                             "abcdefghijklmnopqrstuvwxyz",
                             "ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $set = $default_arr[0].$default_arr[1].$default_arr[2];
        $random_string = "";
        for($i=0;$i<$length;$i++)
            $random_string .= $set[mt_rand(0,strlen($set)-1)];
        return $random_string;
    }
	
	//echo $_SERVER['REDIRECT_REMOTE_USER']; die();
	
	if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] === $_SERVER['PHP_SELF'])
		die("Redirection Loop Detected");
	else if($_SERVER['REQUEST_METHOD'] === "POST")
	{
		$sqli = new mysqli("localhost", "nbaztec", "");
		if ($sqli->connect_error) 
			die('Connect Error ('.$sqli->connect_errno.') '.$sqli->connect_error);
		$sqli->select_db("test");
		
		$PARCEL = array("error" => "", "url" => "");
		$pat = "|^https?://(?:www\.)?(?:[\w-.]+)(?:/[/%&?=\w.-]+)?$|i";
		$no_js = isset($_POST['no-js'])? $_POST['no-js']: false;
		$custom = isset($_POST['custom'])? preg_replace("|[^\w-]|","-",$_POST['custom']) : NULL;
		$url = isset($_POST['url']) && preg_match($pat, $_POST['url'])? htmlentities($_POST['url']): NULL;	
		
		if($url)
		{
			$r = $sqli->query("SELECT id FROM `urls` WHERE url='$url'");			
			if($r->num_rows === 0)
			{
				$sqli->query("INSERT INTO `urls`(url) VALUES('$url')");				
					$id = $sqli->insert_id;
				if(!$r)
					die('Query Error ('.$sqli->connect_errno.') '.$sqli->connect_error);
			}
			else
			{
				$row = $r->fetch_assoc();				
				$id = $row['id'];
				$id = $row['id'];
			}
	
			// Insert into custom
			if($custom)
			{				
				$sqli->query("INSERT INTO `mapping` VALUES('$custom', $id)");
				if($sqli->errno == 1062)
					$PARCEL['error'] = "Entry ".$custom." is already taken";
			}
			else
			{			
				do
				{
					$custom = getRandomKey(4);				
					$sqli->query("INSERT INTO `mapping` VALUES('$custom', $id)");
				}while($sqli->errno == 1062);
			}
			$PARCEL['url'] = $custom;
		}
		else
		{
			$PARCEL['error'] = "Malformed URL";
		}
		//echo preg_match($pat, "http://www.nbaztec.co.in", $match);
		//echo $match[1]."|".$match[2];	
		$sqli->commit();
		$sqli->close();

		if($no_js == "false")
			echo json_encode($PARCEL);		
		//print_r($PARCEL);
		
	}
	else if($_SERVER['REQUEST_METHOD'] === "GET" && isset($_GET['key']))
	{
		$PARCEL = array("error" => "", "url" => "");
		$custom = $_GET['key'];
				$sqli = new mysqli("localhost", "nbaztec", "");
		if ($sqli->connect_error) 
			die('Connect Error ('.$sqli->connect_errno.') '.$sqli->connect_error);
		$sqli->select_db("test");
		if($custom)
		{			
			$r = $sqli->query("SELECT url FROM `urls` AS u INNER JOIN `mapping` AS m ON u.id=m.id WHERE m.key='$custom'");
			if($sqli->errno === 0)
			{
				$row = $r->fetch_assoc();
				$u = html_entity_decode($row['url']);
				/*
				 * Custom way to detect redirect loop, but client will handle it anyway
				 *
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL,            $u); 
				curl_setopt($ch, CURLOPT_HEADER,         true); 
				curl_setopt($ch, CURLOPT_NOBODY,         true); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($ch, CURLOPT_TIMEOUT,        15); 		
				curl_exec($ch); 
				$e = curl_errno($ch);
				curl_close($ch);
				if($e)
				{
					echo "REDIRECT ERROR";					
					die();
				}
				*/				
				header( "HTTP/1.1 301 Moved Permanently" ); 
				header("Location: ".html_entity_decode($row['url']));				
				
			}
			else
				$PARCEL['error'] = "No such url exists";
			//print_r($PARCEL);
		}
		
	}			
?>