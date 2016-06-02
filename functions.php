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

function changePassword($newPassword1, $newPassword2, $userID) {
	if ($newPassword1 != $newPassword2) {
		echo "Nowe hasła nie są zgodne";
		return;
	}
	if (strlen($newPassword1) < 8) {
		echo "Hasło powinno być dłuższe niż 8 znaków";
		return;
	}
	if (strlen($newPassword1) > 16) {
		echo "Hasło powinno być krótsze niż 16 znaków";
		return;
	}
	
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
	$password_hash = crypt($newPassword1, $salt);
	
	$conn = getDBConnection();
	$update_user = "update users set password_hash='$password_hash', salt='$salt' where user_id=$userID";
	$conn->query($update_user);
	$delete_passwords = "delete from passwords where user_id=$userID";
	$conn->query($delete_passwords);
		
	$passwordLength = strlen($newPassword1);
	$partialLength = floor($passwordLength/2);
	if ($partialLength < 5) {
		$partialLength = 5;
		createPasswords($userID, $newPassword1, $passwordLength, $partialLength, $salt);
	} else {
		$i = 5;
		while ($i <= $partialLength) {
			createPasswords($userID, $newPassword1, $passwordLength, $i, $salt);
			$i++;
		}
	}
		
	$conn->close();
}

// Rejestracja użytkownika
function register($username, $password) {
	if (strlen($password) < 8) {
		echo "Hasło powinno być dłuższe niż 8 znaków";
		return;
	}
	if (strlen($password) > 16) {
		echo "Hasło powinno być krótsze niż 16 znaków";
		return;
	}
	
	$conn = getDBConnection();
	$select1 = "select max(user_id) as max_id from users";
	$query1 = $conn->query($select1);
	$row1 = $query1->fetch_assoc();
	$userIDreg = $row1["max_id"];
	
	$select2 = "select max(user_id) as max_id from unregistered_users";
	$query2 = $conn->query($select2);
	$row2 = $query2->fetch_assoc();
	$userIDunreg = $row2["max_id"];
	$conn->close();

	if ($userIDreg < $userIDunreg) {
		$userID = $userIDunreg+1;
	} else if ($userIDreg > $userIDunreg) {
		$userID = $userIDreg+1;
	} else {
		$userID = $userIDunreg+1;
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
		$password_hash = crypt($password, $salt);
		$question_number = rand();
		$conn = getDBConnection();
		$insert_user = "insert into users (user_id, username, password_hash, salt, block_after, ret_question, ret_answer) values
						($userID, '$username', '$password_hash', '$salt', 8, 'Nie jestes robotem? Wpisz $question_number', '$question_number')";
		$query = $conn->query($insert_user);
		$userID = $conn->insert_id;
		
		$passwordLength = strlen($password);
		$partialLength = floor($passwordLength/2);
		if ($partialLength < 5) {
			$partialLength = 5;
			createPasswords($userID, $password, $passwordLength, $partialLength, $salt);
		} else {
			$i = 5;
			while ($i <= $partialLength) {
				createPasswords($userID, $password, $passwordLength, $i, $salt);
				$i++;
			}
		}
		
		$conn->close();
		
		return true;
	} else {
		echo "Podany użytkownik istnieje";
	}
	return false;
}
function randLetter() {
    $int = rand(0, 51);
    $a_z = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $rand_letter = $a_z[$int];
    return $rand_letter;
}
function createPasswords($userID, $password, $passwordLength, $partialLength, $salt) {
	//$p = getNumberOfCombinations($password, $passwordLength, $partialLength);
	// Ograniczenie ilosci czesciowych hasel kazdej dlugosci, aby operacja wstawiania do bazy trwala krocej
	/*if ($passwordLength >= 11) if ($p > 50) $p = 11;
	if ($passwordLength >= 13) if ($p > 25) $p = 11;
	if ($passwordLength >= 15) if ($p > 15) $p = 11;*/
	$p = 11;
	$masks = array();
	while (count($masks) < $p) {
		$mask = createMask($password, $passwordLength, $partialLength);
		if (!in_array($mask, $masks)) {
			array_push($masks, $mask);
	
			$partial_password_hash = createPartialPasswordHash($mask, $password, $salt);
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
	
	return crypt(implode($resultPasswordArray), $salt);
}
function reCreatePartialPasswordHash($mask, $password, $salt) {
	return crypt($password, $salt);
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

function getUserIDFromDBunreg($username) {
	$conn = getDBConnection();
	$select = "select user_id
				from unregistered_users
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row["user_id"];
}

function getUserSaltFromDB($username) {
	$conn = getDBConnection();
	$select = "select salt
				from users
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row["salt"];
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
	if ($userID == null) {
		$userID = getUserIDFromDBunreg($username);
	}
	
	$conn = getDBConnection();
	$select = "select mask
				from passwords
				where mask='$mask' and user_id=$userID and is_used=0 and last_used=1";
	$query = $conn->query($select);
		
	$mask = $query->fetch_assoc();
	
	$conn->close();
	
	return implode($mask);
}

function getPartialHash($username, $mask) {
	$userID = getUserIDFromDB($username);
	
	$conn = getDBConnection();
	$select = "select partial_password_hash
				from passwords
				where mask='$mask' and user_id=$userID and is_used=0 and last_used=1";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row["partial_password_hash"];
}

function setPasswordChecked($mask, $username) {
	$userID = getUserIDFromDB($username);
	
	$conn = getDBConnection();
	$update = "update passwords set last_used=0, is_used=1 where user_id=$userID and mask='$mask'";
	$conn->query($update);
	$conn->close();
}


// f_login
// Sprawdzenie, czy zalogowano
function isLoggedIn() {
	$user_id = getUserFromSession()["user_id"];
	if (!isset($user_id)) return false;
	return true;
}

// Pobieranie danych z sesji
function getUserFromSession() {
	session_start();
	return $_SESSION;
}

// Sprawdzenie, czy użytkownik jest zarejestrowany lub
// wcześniej próbował się logować
function isUsernameExistsInTable($username, $table) {
	$conn = getDBConnection();
	$select = "select user_id
				from $table
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	if ($row == null) return false;
	return true;
}

// Sprawdzenie, czy hasło jest prawidlowe dla podanego
// użytkownika
function isUserPasswordCorrect($password, $username) {
	$conn = getDBConnection();
	$select = "select user_id
				from users
				where password_hash = '$password' and
						username = '$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	if ($row == null) return false;
	return true;
}

// Logujemy użytkownika do sesji
function login($username) {
	$conn = getDBConnection();
	$select = "select user_id
				from users
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	session_start();
	$_SESSION["user_id"] = $row["user_id"];
	$_SESSION["username"] = $username;
	$_SESSION["last_login"] = date("Y-m-d G:i:s");
}

// Wylogowanie z sesji
// oraz dodanie daty ostatniego logowania do bazy danych
function logout() {
	$userId = getUserFromSession()["user_id"];
	$lastLogin = getUserFromSession()["last_login"];
	
	$conn = getDBConnection();
	$update = "update users
				  set last_login='$lastLogin',
				  	login_attempts=0
				  where user_id=$userId";
	$conn->query($update);
	$conn->close();
	
	session_destroy();
}

// Dodanie użytkownika do niezarejestrowanych i
// powiększenie ilości i datę próby logowania
function addUnregisteredLoginAttempt($username) {
	$conn = getDBConnection();
	$ifBlock = rand(1, 2);

	$select1 = "select max(user_id) as max_id from users";
	$query1 = $conn->query($select1);
	$row1 = $query1->fetch_assoc();
	$userIDreg = $row1["max_id"];
	
	$select2 = "select max(user_id) as max_id from unregistered_users";
	$query2 = $conn->query($select2);
	$row2 = $query2->fetch_assoc();
	$userIDunreg = $row2["max_id"];

	if ($userIDreg < $userIDunreg) {
		$userID = $userIDunreg+1;
	} else if ($userIDreg > $userIDunreg) {
		$userID = $userIDreg+1;
	} else {
		$userID = $userIDunreg+1;
	}
	
	if ($ifBlock == 1) {
		$r1 = rand(1, 10);
		$r2 = rand();
		$insert = "insert into unregistered_users 
					(user_id, username, last_bad_login, login_attempts, block_after, ret_question, ret_answer) values
					($userID, '$username', sysdate(), 0, $r1, 'Nie jestes robotem? Wpisz $r2', '$r2')";
		$conn->query($insert);
		$conn->close();
	} else {
		$r1 = rand(1, 10);
		$r2 = rand();
		$insert = "insert into unregistered_users 
					(user_id, username, last_bad_login, login_attempts, block_after, login_attempts_block) values
					($userID, '$username', sysdate(), 0, 0, 0)";
		$conn->query($insert);
		$conn->close();
	}
	
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
	$randPasswordLength = rand(5, 16);
	$randPassword = "";
	for ($i = 1; $i <= $randPasswordLength; $i++) {
		$letter = randLetter();
		$number = rand(1, 10);
		$randPassword = $randPassword . $letter . $number;
	}
	if ($randPasswordLength <= 9) {
		$partialLength = 5;
	} else {
		$partialLength = floor($randPasswordLength/2);
	}
	createPasswords($userID, $randPassword, $randPasswordLength, $partialLength, $salt);
	
	increaseUserLoginAttempts($username, "unregistered_users");
}

function isBlock($username, $table) {
	$conn = getDBConnection();
	$select = "select block_after
			  	  from $table
			  	  where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	if ($row["block_after"] == 0) return false;
	return true;
}

// Powiekszenie ilosci i daty proby logowania użytkownika
// zarejestowanego lub niezarejestowanego
function increaseUserLoginAttempts($username, $table) {
	$conn = getDBConnection();
	
	$nowPlus50s = strtotime("+50 seconds", strtotime(date("Y-m-d G:i:s")));
	$unlockLoginTime = date("Y-m-d G:i:s", $nowPlus50s);
	
	if (loginInfo($username, $table)["login_attempts"] >= 4) {	
		$update = "update $table
					set last_bad_login=sysdate(),
						login_attempts=login_attempts+1,
						unlock_login_time='$unlockLoginTime',
						login_attempts_block=login_attempts_block+1
					where username='$username'";
	} else {
		$update = "update $table
					set last_bad_login=sysdate(),
						login_attempts=login_attempts+1,
						login_attempts_block=login_attempts_block+1
					where username='$username'";
	}
	
	$conn->query($update);
	$conn->close();
}

// Sprawdzenie, czy można już zablokować logowanie użytkownika
function isEnableToLockUser($username, $table) {
	if (loginInfo($username, $table)["login_attempts"] >= 5) {
		$timeNow = strtotime(date("Y-m-d G:i:s"));
		$unlockLoginTime = strtotime(loginInfo($username, $table)["unlock_login_time"]);
		if ($timeNow < $unlockLoginTime) return true;
		else {
			$conn = getDBConnection();
			$update = "update $table
						set login_attempts=0,
							unlock_login_time=0
						where username='$username'";
			$conn->query($update);
			$conn->close();
		}
	}
	return false;
}

// Sprawdzenie, czy można już zablokować konto użytkownika
function isEnableToBlockUser($username, $table) {
	if (loginInfo($username, $table)["login_attempts_block"] >= loginInfo($username, $table)["block_after"]
		&& loginInfo($username, $table)["block_after"] > 0)
		return true;
	return false;
}

// Pobranie informacji użytkownika
function loginInfo($username, $table) {
	$conn = getDBConnection();
	$select = "select *
				from $table
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row;
}

// Obliczanie czasu do konca blokowania logowania
function timeToEndLock($username, $table) {
	$nowTime = time();
	$endTime = strtotime(loginInfo($username, $table)["unlock_login_time"]);
	return floor(($endTime - $nowTime) % 60);
}


// Pobranie informacji użytkownika
function getUserLoginInfo() {
	$user_id = getUserFromSession()["user_id"];
	
	$conn = getDBConnection();
	$select = "select last_login, last_bad_login, login_attempts
			  	  from users
			  	  where user_id=$user_id";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row;
}


// f_connect

function getAllMessages() {
		$conn = getDBConnection();
		
		$sql = "select message_id, text, modified
				from messages";
		$query = $conn->query($sql);
		
		$conn->close();
		
		if ($query->num_rows > 0) {
	    	return $query;
		} else {
    		return null;
		}
	}
	
function getUsernameForMessage($message_id) {
		$conn = getDBConnection();
		
		$sql = "select u.username
				from users u, messages m, allowed_messages a
				where u.user_id = a.user_id and
					m.message_id = a.message_id and
					m.message_id = $message_id";
		$query = $conn->query($sql);
		$row = $query->fetch_assoc();
		
		$conn->close();
		
		return $row["username"];
	}
	
function isAllowedToEdit($message_id, $user_id) {
		$conn = getDBConnection();
		
		$sql = "select user_id
				from allowed_messages
				where message_id = $message_id and
					user_id = $user_id";
		$query = $conn->query($sql);
		
		$conn->close();
		
		if ($query->num_rows > 0) {
	    	return true;
		} else {
    		return false;
		}
	}
	
function getOwner($message_id) {
		$conn = getDBConnection();
		
		$sql = "select owner
				from users u, messages m, allowed_messages a
				where u.user_id = a.user_id and
					m.message_id = a.message_id and
					m.message_id = $message_id";
		$query = $conn->query($sql);
		$row = $query->fetch_assoc();
		
		$conn->close();
		
		return $row["owner"];
	}
	
function addMessage($message_text) {
		$conn = getDBConnection();
		
		$user_id = getUserFromSession()["user_id"];
		
		$insert_message = "insert into messages (text, owner) values ('$message_text', $user_id)";
		$conn->query($insert_message);
	
		$user_id = getUserFromSession()["user_id"];
		$message_id = $conn->insert_id;
		$insert_allowed_message = "insert into allowed_messages (user_id, message_id) values ($user_id, $message_id)";
		$conn->query($insert_allowed_message);
		
		$conn->close();
	}
	
function deleteMessage($message_id, $owner) {
		$conn = getDBConnection();
		
		$logged_user_id = getUserFromSession()["user_id"];
		
		if ($logged_user_id == $owner) {		
			$delete_from_allowed_messages = "delete from allowed_messages where message_id=$message_id";
			$conn->query($delete_from_allowed_messages);
			
			$delete_from_messages = "delete from messages where message_id=$message_id";
			$conn->query($delete_from_messages);
		}
		
		$conn->close();
	}
	
function getMessageText($message_id) {
		$conn = getDBConnection();
		
		$sql = "select text 
				from messages
				where message_id = $message_id";
		$query = $conn->query($sql);
		$row = $query->fetch_assoc();
		
		$conn->close();
		
		return $row["text"];
	}
	
function getUsersButNotOwner($owner) {
		$conn = getDBConnection();
		
		$sql = "select user_id, username
				from users
				where user_id <> $owner";
		$query = $conn->query($sql);
		
		$conn->close();
		
		if ($query->num_rows > 0) {
	    	return $query;
		} else {
    		return null;
		}
	}
	
function updateMessage($message_id, $message, $user_id_for_permissions, $owner) {
		$conn = getDBConnection();
		
		$logged_user_id = getUserFromSession()["user_id"];
		
		$sql = "select user_id
				from allowed_messages
				where message_id = $message_id";
		$query = $conn->query($sql);
		
		while($row = $query->fetch_assoc()) {
			$user_with_permissions = $row["user_id"];
			if ($logged_user_id == $user_with_permissions) {
				$update_messages = "update messages set text='$message' where message_id=$message_id";
				$conn->query($update_messages);
			}
		}

		if ($logged_user_id == $user_with_permissions) {
			$update_messages = "update messages set text='$message' where message_id=$message_id";
			$conn->query($update_messages);
		}
				
		if ($logged_user_id == $owner) {
			if ($user_id_for_permissions == 0) {
				$remove_allowed_messages = "delete from allowed_messages where message_id=$message_id and user_id<>$owner";
				$conn->query($remove_allowed_messages);
			} else {
				$add_allowed_messages = "insert into allowed_messages (message_id, user_id) values ($message_id, $user_id_for_permissions)";
				$conn->query($add_allowed_messages);
			}
		}
		
		$conn->close();
	}



// f_retrieve
// Sprawdzenie, czy użytkownik może odzyskać hasło
function isUsernameCanRetrieve($username, $table) {
	$conn = getDBConnection();
	$select = "select block_after
				from $table
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	if ($row["block_after"] > 0) return true;
	return false;
}

// Pobieranie pytania dla podanego użytkownika
function getQuestion($username) {
	$conn = getDBConnection();
	$select = "select ret_question
				from users
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	if ($row == null) {
		$select = "select ret_question
					from unregistered_users
					where username='$username'";
		$query = $conn->query($select);
		$row = $query->fetch_assoc();
		$conn->close();
		return $row["ret_question"];
	}
	$conn->close();
	
	return $row["ret_question"];
}

// Sprawdzenie, czy odpowiedź jest prawidłowa
function isAnswerMatch($username, $answer) {
	$conn = getDBConnection();
	$select = "select ret_answer
				from users
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	if ($row == null) {
		$select = "select ret_answer
					from unregistered_users
					where username='$username'";
		$query = $conn->query($select);
		$row = $query->fetch_assoc();
		$conn->close();
		if (strtolower($answer) == strtolower($row["ret_answer"])) return true;
		return false;
	}
	$conn->close();
	
	if (strtolower($answer) == strtolower($row["ret_answer"])) return true;
	return false;
}

// Sprawdzenie, czy użytkownik jest zablokowany
function isUserBlocked($username, $table) {
	$conn = getDBConnection();
	$select = "select is_blocked
				from $table
				where username='$username'";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row["is_blocked"];
}

function isBlockEnable() {
	$userId = getUserFromSession()["user_id"];
	$conn = getDBConnection();
	$select = "select block_after
			  	  from users
			  	  where user_id=$userId";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	if ($row["block_after"] == 0) return false;
	return true;
}
// Liczba nieudanych logowań, po których nastąpi blokada konta
function getNumberOfAttemptsToBlock() {
	$userId = getUserFromSession()["user_id"];
	$conn = getDBConnection();
	$select = "select block_after
			  	  from users
			  	  where user_id=$userId";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row["block_after"];
}
// Włączenie / Wyłączenie blokowania konta
function setBlock($isBlock, $blockAttempts) {
	$userId = getUserFromSession()["user_id"];
	$conn = getDBConnection();
	if ($isBlock == 1) {
		$update = "update users
		  	   set block_after=$blockAttempts
		  	   where user_id=$userId";
	} else {
		$update = "update users
		  	   set block_after=$blockAttempts,
		  	   	login_attempts_block=0
		  	   where user_id=$userId";
	}
	$conn->query($update);
	$conn->close();
}
// Odzyskiwanie konta po blokadzie
function setRetrieve($question, $answer) {
	$userId = getUserFromSession()["user_id"];
	$conn = getDBConnection();
	$update = "update users
		  	   set ret_question='$question',
			  	   ret_answer='$answer'
			  where user_id=$userId";
	$conn->query($update);
	$conn->close();
}
function getRetrieveQuestionAndAnswer() {
	$userId = getUserFromSession()["user_id"];
	$conn = getDBConnection();
	$select = "select ret_question, ret_answer
			  	  from users
			  	  where user_id=$userId";
	$query = $conn->query($select);
	$row = $query->fetch_assoc();
	$conn->close();
	
	return $row;
}

// Zablokowanie konta
function blockUser($username, $table) {
	$conn = getDBConnection();
	$update = "update $table
				set is_blocked=true
				where username='$username'";
	$conn->query($update);
	$conn->close();
}

// Odblokowanie konta
function unBlockUser($username, $table) {
	$conn = getDBConnection();
	$update = "update $table
				set is_blocked=false,
					login_attempts=0,
					login_attempts_block=0
				where username='$username'";
	$conn->query($update);
	$conn->close();
}


?>
