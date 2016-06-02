<html>
<header>
	<title>BAI3</title>
<header>

<?php
include("functions.php");
?>

<body>

<?php

if (isset($_GET["getUserAction"])) {
	$username = $_GET["username"];
	
	$registered = "users";
	$unregistered = "unregistered_users";

	if (isUsernameExistsInTable($username, $registered) && !isUserBlocked($username, $registered)) {
		if (!isEnableToLockUser($username, $registered) && !isEnableToBlockUser($username, $registered)) {
			$userID = getUserIDFromDB($username);
			$mask = getMask($userID);
			?>		
		<form id="login" action="login.php" method="get">
	Hasło:<br />
<?php
		$disabled = "";
		for ($i = 0; $i < count($mask); $i++) {
			if ($mask[$i] == '1') {
				$disabled = "";
			} else {
				$disabled = "disabled";
			}
		echo "<input type='password' name='p[$i]' maxlength='1' $disabled size='1' /> ";
		}
?>
			<input type="text" name="username" value="<?php echo $username; ?>" hidden />
			<input type="submit" name="loginAction" value="Zaloguj" />
		</form>
		<a href="index.php">Powrot</a>
<?php
		}
		
		if (isEnableToLockUser($username, $registered) && !isEnableToBlockUser($username, $registered)) {
			echo "Zablokowano możliwość logowania użytkownika $username na " . timeToEndLock($username, $registered) . " sekund.<br /><br />";
			echo "<a href='index.php'>Powrot</a>";
		}
		
		if (isEnableToBlockUser($username, $registered)) {
			blockUser($username, $registered);
			echo "Konto zablokowane.<br />Odpowiedz na wcześniej zdefiniowane pytanie, aby odblokować dostęp do konta. <br /><br />";
			include("retrieve.php");
		}
	} else if (isUserBlocked($username, $registered)) {
		echo "Konto zablokowane.<br />Odpowiedz na wcześniej zdefiniowane pytanie, aby odblokować dostęp do konta. <br /><br />";
		include("retrieve.php");
	} else if (isUserBlocked($username, $unregistered)) {
		echo "Konto zablokowane.<br />Odpowiedz na wcześniej zdefiniowane pytanie, aby odblokować dostęp do konta. <br /><br />";
		include("retrieve.php");
	} else {
		if (isUsernameExistsInTable($username, $unregistered)) {
			if (!isEnableToLockUser($username, $unregistered) && !isEnableToBlockUser($username, $registered)) {
				$userID = getUserIDFromDBunreg($username);
				$mask = getMask($userID);
?>		
				<form id="login" action="login.php" method="get">
					Hasło:<br />
<?php
					$disabled = "";
				for ($i = 0; $i < count($mask); $i++) {
					if ($mask[$i] == '1') {
						$disabled = "";
					} else {
						$disabled = "disabled";
					}
					echo "<input type='text' name='p[$i]' maxlength='1' $disabled size='1' /> ";
				}
?>
					<input type="text" name="username" value="<?php echo $username; ?>" hidden />
					<input type="submit" name="loginAction" value="Zaloguj" />
				</form>
				<a href="index.php">Powrot</a>
<?php
			}
			
			if (isEnableToLockUser($username, $unregistered) && !isEnableToBlockUser($username, $unregistered)) {
				echo "Zablokowano możliwość logowania użytkownika $username na " . timeToEndLock($username, $unregistered) . " sekund.<br /><br />";
				echo "<a href='index.php'>Powrot</a>";
			}
			
			if (isEnableToBlockUser($username, $unregistered)) {
				blockUser($username, $unregistered);
				echo "Konto zablokowane.<br />Odpowiedz na wcześniej zdefiniowane pytanie, aby odblokować dostęp do konta. <br /><br />";
				include("retrieve.php");
			}
		} else {
			addUnregisteredLoginAttempt($username);
			
			$userID = getUserIDFromDBunreg($username);
				$mask = getMask($userID);
?>		
				<form id="login" action="login.php" method="get">
					Hasło:<br />
<?php
					$disabled = "";
				for ($i = 0; $i < count($mask); $i++) {
					if ($mask[$i] == '1') {
						$disabled = "";
					} else {
						$disabled = "disabled";
					}
					echo "<input type='text' name='p[$i]' maxlength='1' $disabled size='1' /> ";
				}
?>
					<input type="text" name="username" value="<?php echo $username; ?>" hidden />
					<input type="submit" name="loginAction" value="Zaloguj" />
				</form>
				<a href="index.php">Powrot</a>
<?php
		}
	}
}
?>

</body>
</html>

<?php

if (isset($_GET["loginAction"])) {
 	$partialPassword = $_GET["p"];
	$username = $_GET["username"];
	
	$registered = "users";
	$unregistered = "unregistered_users";
	
	$mask = retrieveMask($partialPassword, $username);
	
	if ($mask != null && isUsernameExistsInTable($username, $registered)) {
		$partialHash = getPartialHash($username, $mask);
		$salt = getUserSaltFromDB($username);
		$currentHash = reCreatePartialPasswordHash(str_split($mask), implode($partialPassword), $salt);
		
		if ($partialHash == $currentHash) {
			echo "Poprawne haslo";
			setPasswordChecked($mask, $username);
			login($username);
			header("location: messages.php");
		} else {
			increaseUserLoginAttempts($username, $registered);
			echo "Nieprawidłowe hasło.<br /><br />";
			echo "<a href='index.php'>Powrot</a>";
		}
	} else if (isUsernameExistsInTable($username, $registered)) {
		increaseUserLoginAttempts($username, $registered);
		echo "Nieprawidłowe hasło.<br /><br />";
		echo "<a href='index.php'>Powrot</a>";
	} else if (isUsernameExistsInTable($username, $unregistered)) {
		increaseUserLoginAttempts($username, $unregistered);
		echo "Nieprawidłowe hasło.<br /><br />";
		echo "<a href='index.php'>Powrot</a>";
	}
}

if (isset($_GET["unBlockAction"])) {
	$username = trim($_GET["username"]);
	$answer = trim($_GET["answer"]);
	
	if (!isAnswerMatch($username, $answer)) {
		echo "Odpowiedź nieprawidłowa. Spróbuj ponownie.<br /><br />";
		include("retrieve.php");
	} else {
		$registered = "users";
		$unregistered = "unregistered_users";
		unBlockUser($username, $registered);
		unBlockUser($username, $unregistered);
		echo "Konto odblokowane<br /><br />";
		echo "<a href='index.php'>Powrot</a>";
	}
}

?>

