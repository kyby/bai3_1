<html>
<head><title>Login</title></head>

<?php
include("functions.php");
if (!isLoggedIn()) {
	header("location: login.php");
}
?>

<body>

	<p>Witaj <?php echo getUserFromSession()["username"]; ?>
	<input type="button" value="Wyloguj" onClick="location.href='logout.php'" /></p>
	
	<input type="button" value="Wiadomości" onClick="location.href='messages.php'" /></p>
	<br />

	<h3>Statystyki logowania</h3>
	<table>
		<tr>
			<th></th>
			<th></th>
		</tr>
		<tr>
			<td>Ostatnie udane logowanie</td>
			<td><?php
					$last_login = getUserLoginInfo()["last_login"];
					if ($last_login == 0) echo "Zalogowano pierwszy raz";
					else echo $last_login;
				 ?>
			</td>
		</tr>
		<tr>
			<td>Ostatnie nieudane logowanie</td>
			<td><?php
					$last_bad_login = getUserLoginInfo()["last_bad_login"];
					if ($last_bad_login == 0) echo "Nigdy";
					else echo $last_bad_login;
				 ?>
			</td>
		</tr>
		<tr>
			<td>Liczba nieudanych logowań od ostatniego logowania</td>
			<td><?php echo getUserLoginInfo()["login_attempts"]; ?></td>
		</tr>
	</table>
	
	<br />
	
	<h3>Blokowanie konta</h3>
	<?php
		$blockAttempts = "";
		$disabled = "";
		$isBlockEnable = isBlockEnable();
		if ($isBlockEnable) {
			$disabled = "disabled";
			$blockAttempts = getNumberOfAttemptsToBlock();
		}
	?>
	<form id="block" action="settings.php" method="get" >
		Blokowanie konta po: <input type="text" name="block_attempts" value="<?php if ($blockAttempts == 0) echo "8"; else echo $blockAttempts; ?>" <?php echo $disabled; ?> /> nieudanych próbach logowania<br />
		Pytanie bezpieczeństwa: <input type="text" name="question" value="<?php echo getRetrieveQuestionAndAnswer()["ret_question"]; ?>" <?php echo $disabled; ?> /><br />
		Odpowiedź bezpieczeństwa: <input type="text" name="answer" value="<?php echo getRetrieveQuestionAndAnswer()["ret_answer"]; ?>" <?php echo $disabled; ?> /><br />
		<input type="submit" name="blockAction" value="<?php echo ($isBlockEnable ? 'Wyłącz' : 'Włącz'); ?>" />
	</form>
	
	<h3>Zmiana hasła</h3>
	<!--<form id="pass_change" action="settings.php" method="get">
	</form>-->

</body>
</html>

<?php
$blockAction = $_GET["blockAction"];
if (isset($blockAction)) {
	if ($blockAction == "Włącz") {
		$question = trim($_GET["question"]);
		$answer = trim($_GET["answer"]);
		$blockAttempts = trim($_GET["block_attempts"]);
	
		if ($question == "" || $answer == "" || $blockAttempts == "") {
			echo "Wszystkie pola muszą być wypełnione.";
		} else {
			setBlock(1, $blockAttempts);
			setRetrieve($question, $answer);
			
			header("location: settings.php");
		}
	} else {
		setBlock(0, 0);
		
		header("location: settings.php");
	}
}
?>

