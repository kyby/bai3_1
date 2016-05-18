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
		
		// TODO: Create mask and hash for all password possibilities.
		
		$conn->close();
	}
}
function randLetter() {
    $int = rand(0,51);
    $a_z = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $rand_letter = $a_z[$int];
    return $rand_letter;
}

?>
