<?php

$db_location = "localhost";
$db_user = "root";
$db_pw = "toor";
$db_schema = "formserver";

#########################################################################################################################
$failed = false;
$body = (json_decode(file_get_contents('php://input'), true));

#print_r($body);

//check if we have a DB set
if (!isset($body['name']))
{
	$body['response'] = "Survey Name not provided";
	$failed = true;
}
else if (!isset($body['version']))
{
	$body['response'] = "Survey version not provided";
	$failed = true;
}
else if (!isset($body['filedir']))
{
	$body['response'] = "Survey file directory not provided";
	$failed = true;
}
else if (!isset($body['valid']))
{
	$body['response'] = "Survey valid value not provided";
	$failed = true;
}
else if (!isset($body['contents']) || $body['contents'] == "")
{
	$body['response'] = "Contents not provided";
	$failed = true;
}
else
{
	$db = new mysqli($db_location,$db_user,$db_pw,$db_schema);
	if ($db->connect_errno)
	{
		$body['response'] = "Database Error: ".$db->connect_error;
		$failed = true;
	}
}


if ($failed)
{
	echo json_encode($body);
	http_response_code(500);
}
else
{
	$id = rand();
	$name = $body['name'];
	$version = 1;
	$file_dir = $body['filedir'];
	$valid = $body['valid'];
	$data = $body['contents'];
	$hash = 'testing'; //this is hardcoded for now

	$check_sql = "SELECT * FROM UNCOMPLETED_SURVEY WHERE form_name='".$name."'";
	$result = $db->query($check_sql);
	if (!$result)
	{
		//this REALLY shouldn't happen.
		$body['response'] = "Error reading from the DB. Something is broken!".$db->error;
		$failed = true;
	}
	else if ($result->num_rows > 0)
	{
		$version = $result->num_rows + 1;
	}

	if (!$failed)
	{

		$sql = "INSERT INTO UNCOMPLETED_SURVEY (id,version,form_name,file_directory,valid,data_dump)";
		$sql .= "VALUES (".$id.",".$version.",";
		$sql .= "'".$name."','".$file_dir."',".$valid.",'".$data."')";

		$sql2 = "INSERT INTO PERMITTED_PATIENT_HASH (hash, id)";
		$sql2 .= "VALUES ('".$hash."',".$id.")";

		//$body['sql'] = $sql; //delete this! This is UNSAFE as the world can see it!
		if (!$result = $db->query($sql))
		{
			//oh no, the query failed!
			$body['response'] = "Error inserting form data with id ".$id." and version ".$version." into DB: ".$db->error;
			$failed = true;
		}
		else if (!$result2 = $db->query($sql2))
		{
			//oh no, the query failed!
			$body['response'] = "Error inserting hash into DB: ".$db->error;
			$failed = true;
		}
		else
		{
			$body['response'] = "Data inserted and hash added";
			$failed = true;
		}
	}

	echo json_encode($body);
	//if ($failed)
		//http_response_code(500);
	//else
		http_response_code(200);
}

?>
