<?php

// Połączenie z bazą danych
function getDBConnection() {
	$conn = new mysqli("localhost", "root", "pass", "bai3");
	if ($conn->connect_error) {
		die("Połączenie nieudane: " . $conn->connect_error);
	}
	return $conn;
}

// Sprawdzenie, czy użytkownik jest zarejestrowany
function isUsernameExists($username) {
	$conn = getDBConnection();
	$select = "select user_id
				from users
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	if ($row == null) return false;
	return true;
}

// Rejestracja użytkownika
function register($username, $password) {
	if (strlen($password) < 8 || strlen($password) > 16) {
		echo "error password length";
		return;
	}

	if (!isUsernameExists($username)) {
		$salt = randLetter() .
				  rand(1, 10) .
				  randLetter() .
				  rand(1, 10) . 
				  randLetter() .
				  randLetter() . 
				  rand(1, 10) . 
				  rand(1, 10) . 
				  rand(1, 10) . 
				  randLetter();
		$password_hash = crypt($password . $salt);
		$question_number = rand();
		$conn = getDBConnection();
		$insert_user = "insert into users (username, password_hash, salt, block_after, ret_question, ret_answer) values
						('$username', '$password_hash', '$salt', 8, 'Nie jestes robotem? Wpisz $question_number', '$question_number')";
		$query = $conn->query($insert_user);
		$userID = $conn->insert_id;
		
		$passwordLength = strlen($password);
		$partialLength = floor($passwordLength/2);
		if ($partialLength < 5) {
			$partialLength = 5;
			createPasswords($userID, $password, $passwordLength, $partialLength);
		} else {
			$i = 5;
			while ($i <= $partialLength) {
				createPasswords($userID, $password, $passwordLength, $i);
				$i++;
			}
		}
		
		$conn->close();
	}
}
function randLetter() {
    $int = rand(0, 51);
    $a_z = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $rand_letter = $a_z[$int];
    return $rand_letter;
}
function createPasswords($userID, $password, $passwordLength, $partialLength) {
	$p = getNumberOfCombinations($password, $passwordLength, $partialLength);
	// Ograniczenie ilosci czesciowych hasel kazdej dlugosci, aby operacja wstawiania do bazy trwala krocej
	if ($passwordLength >= 11) if ($p > 50) $p = 50;
	if ($passwordLength >= 13) if ($p > 25) $p = 25;
	if ($passwordLength >= 15) if ($p > 15) $p = 15;
	$masks = array();
	while (count($masks) < $p) {
		$mask = createMask($password, $passwordLength, $partialLength);
		if (!in_array($mask, $masks)) {
			array_push($masks, $mask);
	
			$partial_password_hash = createPartialPasswordHash($mask, $password);
			$maskToAdd = implode($mask);
			$conn = getDBConnection();
		   $insert_password = "insert into passwords (user_id, partial_password_hash, number_of_chars, mask) values
										($userID, '$partial_password_hash', $partialLength, '$maskToAdd')";
			$query = $conn->query($insert_password);
			$conn->close();
		}
	}
}
function getNumberOfCombinations($password, $n, $r) {
		$nr = $n - $r;
		
		$ns = 1;
		for ($i = 1; $i <= $n; $i++) {
			$ns = $ns * $i;
		}
		$nrs = 1;
		for ($i = 1; $i <= $nr; $i++) {
			$nrs = $nrs * $i;
		}
		$rs = 1;
		for ($i = 1; $i <= $r; $i++) {
			$rs = $rs * $i;
		}
		
		return $p = $ns / ($nrs * $rs);
}
function createMask($password, $passwordLength, $partialLength) {
	$mask = array_fill(0, 16, 0);
	
	for ($i = 0; $i < $partialLength; $i++) {
		$index = randIndex($mask, $passwordLength-1);
		$mask[$index] = 1;
	}
	
	return $mask;
}
function randIndex($array, $length) {
	$index = rand(0, $length);
	while ($array[$index] == 1) $index = rand(0, $length);
	return $index;
}
function createPartialPasswordHash($mask, $password, $salt) {
	$passwordArray = str_split($password);
	$resultPasswordArray = array();
	
	for ($i = 0; $i < count($mask); $i++) {
		if ($mask[$i] == 1) {
			$resultPasswordArray[$i] = $passwordArray[$i];
		}
	}
	
	return crypt(implode($resultPasswordArray) . $salt);
}


function getUserIDFromDB($username) {
	$conn = getDBConnection();
	$select = "select user_id
				from users
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row["user_id"];
}

function getNumberOfChars($userID) {
	$conn = getDBConnection();
	$select = "select max(number_of_chars) as max_number
				from passwords
				where user_id=$userID and is_used=0";
	$query = $conn->query($select);
	$numOfChars = $query->fetch_assoc();
	
	$numberOfChars = rand(5, $numOfChars["max_number"]);
	
	$conn->close();
	
	return $numberOfChars;
}

function getMask($userID) {
	$conn = getDBConnection();
	$select = "select last_used
							from passwords
							where user_id=$userID and is_used=0 and last_used=1";
	$query = $conn->query($select);
	$isUsingNow = $query->fetch_assoc();
	
	if ($isUsingNow == null) {
		$numberOfChars = getNumberOfChars($userID);
		$select = "select mask
					from passwords
					where user_id=$userID and is_used=0 and number_of_chars=$numberOfChars";
		$query = $conn->query($select);
		$mask = $query->fetch_assoc();
	
		$maskString = implode($mask);
		$update = "update passwords set last_used=1 where user_id=$userID and mask='$maskString'";
		
		$conn->query($update);
	} else {
		$select = "select mask
						from passwords
						where user_id=$userID and last_used=1 and is_used=0";
		$query = $conn->query($select);
		$mask = $query->fetch_assoc();
	}
	
	$conn->close();
	
	return str_split($mask["mask"]);
}

function retrieveMask($partialPassword, $username) {
	$maskArray = array_fill(0, 16, 0);
 	while (list($key, $value) = each($partialPassword)) {
		if ($value != "") $maskArray[$key] = "1";
   }

	$mask = implode($maskArray);
	
	$userID = getUserIDFromDB($username);
	
	$conn = getDBConnection();
	$select = "select mask
				from passwords
				where mask='$mask' and user_id=$userID and is_used=0 and last_used=1";
	$query = $conn->query($select);
	
	//$update = "update passwords set is_used=0 where user_id=$userID and mask='$mask'";
	//$conn->query($update);
		
	$mask = $query->fetch_assoc();
	
	$conn->close();
	
	return implode($mask);
}

?>
