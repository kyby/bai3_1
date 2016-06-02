<html>
<header>
	<title>BAI3</title>
<header>

<?php
include("functions.php");
if (isLoggedIn()) {
	header("location: messages.php");
}
?>

<body>

	<h2>Rejestracja</h2>
	<form id="register" action="register.php" method="get">
		Użytkownik:
		<input type="text" name="username" maxlength="30" required autofocus /><br />
		Hasło:
		<input type="password" name="password" maxlength="30" required /><br />
		<input type="submit" name="registerAction" value="Zarejestruj" />
	</form>

</body>
</html>

<?php

if (isset($_GET["registerAction"])) {
	$username = $_GET["username"];
	$password = $_GET["password"];
	$isReg = register($username, $password);
	
	if ($isReg) {
		echo "Zarejestrowano pomyślnie<br /><br />";
		echo "<a href='index.php'>Powrót do logowania</a><br /><br />";
	}
}

?>
