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
		echo $numberOfChars = getNumberOfChars($userID);
		echo "<br />";
		$mask = getMask($userID, $numberOfChars);
?>		
		<form id="login" action="login.php" method="get">
	Hasło:<br />
<?php
		$disabled = "";
		for ($i = 0; $i < count($mask); $i++) {
			//echo $mask[$i];	// idziemy po kazdym elemencie maski
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
	
	if (isMaskExists($partialPassword)) {
		echo "istnieje taka maska";
	} else {
		echo "taka maska nie istnieje";
	}
}

?>

