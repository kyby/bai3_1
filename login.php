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
	
	if (isUsernameExists($username)) {
		// istnieje
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
		echo "<input type='text' name='p[$i]' maxlength='1' $disabled size='1' /> ";
		}
?>
			<input type="text" name="username" value="<?php echo $username; ?>" hidden />
			<input type="submit" name="loginAction" value="Zaloguj" />
		</form>
<?php
	} else {
		// nie istnieje
		echo "! user nie istnieje";
	}
}
?>

</body>
</html>

<?php

if (isset($_GET["loginAction"])) {
 	$partialPassword = $_GET["p"];
	$username = $_GET["username"];
	
	$mask = retrieveMask($partialPassword, $username);
	
	if ($mask != null) {
		$partialHash = getPartialHash($username, $mask);
		$salt = getUserSaltFromDB($username);
		$currentHash = reCreatePartialPasswordHash(str_split($mask), implode($partialPassword), $salt);
				
		if ($partialHash == $currentHash) {
			echo "Poprawne haslo";
			setPasswordChecked($mask, $username);
			// ustawic last_used = 0 i is_used = 1
		} else {
			echo "Niepoprawne haslo";
			echo "<a href='index.php'>Powrot</a>";
		}
	} else {
		echo "Niepoprawne haslo";
		echo "<a href='index.php'>Powrot</a>";
	}
}

?>

