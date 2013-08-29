<?php
	// setup session details
	session_start();
	// include the database functions
	include('common.php');
	// include AjaxMessage helper class
	include('AjaxMessage.php');
	// include RecipeSQL ORM classes
	include('RecipeSQL.php');
	
	$returnMsg = new AjaxMessage();
	
	$ID = mysql_real_escape_string($_POST['id'],getConnection());
	$AuthorID = $_SESSION['userid'];
	
	$error = "";
	$msg = "";
	$fileElementName = 'fileToUpload';
	if(!empty($_FILES[$fileElementName]['error'])){
		switch($_FILES[$fileElementName]['error']){
			case '1':
				$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case '2':
				$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case '3':
				$error = 'The uploaded file was only partially uploaded';
				break;
			case '4':
				$error = 'No file was uploaded.';
				break;
			case '6':
				$error = 'Missing a temporary folder';
				break;
			case '7':
				$error = 'Failed to write file to disk';
				break;
			case '8':
				$error = 'File upload stopped by extension';
				break;
			case '999':
			default:
				$error = 'No error code avaiable';
		}
        $returnMsg->returnError($error);
	} else if (empty($_FILES['fileToUpload']['tmp_name']) || $_FILES['fileToUpload']['tmp_name'] == 'none') {
		$error = 'No file was uploaded..';
        $returnMsg->returnError($error);
	} else {
        $msg .= " File Name: " . $_FILES['fileToUpload']['name'] . ", ";
        $msg .= " File Size: " . @filesize($_FILES['fileToUpload']['tmp_name']);


        // Create an image sql record for the new file
        $filename = mysql_real_escape_string($_FILES['fileToUpload']['name'],getConnection());

        $image = new Image($ID,$AuthorID,$filename,'','');
        $image->insert();
        $image->FilenameServer = sha1($image->id)  . "_" . $filename;
        $image->update();

        // Move the file from /tmp/ to our image folder ./images with the new hash name
        move_uploaded_file($_FILES['fileToUpload']['tmp_name'],"../images/$image->FilenameServer");

        // Close the database connection
        closeConnection();

        // set the message we are returning
        $returnMsg->returnMessage($msg);
	}
	
	$returnMsg->echoMessage();
?>