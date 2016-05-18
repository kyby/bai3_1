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
		//$query = $conn->query($insert_user);
		
		
		// TODO: get suitable letters from password and create hash of every mask
		
		$p = getNumberOfCombinations($password);
		if ($p > 1000) $p = 1000;
		$masks = array();
		while (count($masks) < $p) {
			$mask = createMask($password);
			if (!in_array($mask, $masks)) array_push($masks, $mask);
		}
		
		echo "<pre>";
		print_r($masks);
		echo "</pre>";
		
		$conn->close();
	}
}
function randLetter() {
    $int = rand(0, 51);
    $a_z = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $rand_letter = $a_z[$int];
    return $rand_letter;
}
function getNumberOfCombinations($password) {
		$n = strlen($password);
		$r = round($n/2);
		if ($r < 5) $r = 5;
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
function createMask($password) {	
	$length = round(strlen($password)/2);
	if ($length < 5) $length = 5;
	
	$mask = array_fill(0, 16, 0);
	
	for ($i = 0; $i < $length; $i++) {
		$index = randIndex($mask);
		$mask[$index] = 1;
	}
	
	return $mask;
}
function randIndex($array) {
	$index = rand(0, count($array)-1);
	while ($array[$index] == 1) $index = rand(0, count($array)-1);
	return $index;
}

?>
