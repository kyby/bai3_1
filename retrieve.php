<html>
<head><title>BAI2 - Retrieve Account</title></head>

<body>

	<form id="retrieve" action="login.php" method="get">
		Pytanie:
		<?php echo getQuestion($username); ?><br />
		<input type="text" name="username" value="<?php echo $username; ?>" hidden />
		Odpowiedź:
		<input type="text" name="answer" required autofocus /><br />
		<input type="submit" name="unBlockAction" value="Odblokuj" />
	</form>

</body>
</html>

