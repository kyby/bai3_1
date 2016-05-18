<html>
<header>
	<title>BAI3</title>
	<?php include("functions.php"); ?>
<header>
<body>

	<h2>Rejestracja</h2>
	<a href="index.php">Powrót do logowania</a><br /><br />
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
	register($username, $password);
}

?>
