<html>
<head><title>Login</title></head>

<?php
include("functions.php");
if (!isLoggedIn()) {
	header("location: index.php");
}

$user_id = getUserFromSession()["user_id"];
$username = getUserFromSession()["username"];
?>

<body>

	<p>Witaj <?php echo $username; ?>
	<input type="button" value="Wyloguj" onClick="location.href='logout.php'" /></p>
	
	<input type="button" value="Ustawienia" onClick="location.href='settings.php'" /></p>
	<br />
	
	<h3>Nowa wiadomość</h3>
	<form id="add_message" action="messages.php" method="get">
		Treść:
		<input type="text" name="par" maxlength="100" required /><br />
		<input type="submit" name="action" value="Dodaj" />
	</form>
		
	<br />
	<h3>Wiadomości</h3>
	<?php include("show_messages.php"); ?>

	<?php
	$action = $_GET["action"];
	if (isset($action)) {
		$par = $_GET["par"];
		
		if ($action == "Dodaj") {
			addMessage($par);
		} else if ($action == "Usuń") {
			if (!isAllowedToEdit($par, $user_id)) {
				echo "<br />Brak uprawnień do edycji";
			} else {
				$owner = getOwner($par);
				deleteMessage($par, $owner);
			}
		} else if ($action == "Edytuj") {
			if (!isAllowedToEdit($par, $user_id)) {
				echo "<br />Brak uprawnień do edycji";
			} else {
	?>
			<h3>Edycja</h3>
			<form id="edit_message" action="messages.php" method="get">
				Wiadomość:<br />
				<textarea type="text" name="par" maxlength="100" cols="40" rows="5" autofocus required ><?php echo trim(getMessageText($par)); ?></textarea><br />
				<input type="text" name="par2" value="<?php echo $par; ?>" hidden />
	<?php
				$owner = getOwner($par);
				if ($owner == $user_id) {
					echo "Dodaj uprawnienia edycji użytkownikowi:<br />";
					echo "<select name='par3'>
							<option value='0'>Usuń wszystkie nadane uprawnienia</option>";
					$users = getUsersButNotOwner($user_id);
					while($row = $users->fetch_assoc()) {
						$user_id_for_permission = $row["user_id"];
						$username_for_permission = $row["username"];
					
						echo "<option value=$user_id_for_permission>$username_for_permission</option>";
					}
					echo "</select><br />";
				}
	?>
				<input type="submit" name="action" value="Zatwierdź" />
			</form>
	<?php
				die();
			}
		} else if ($action == "Zatwierdź") {
			$message = trim($par);
			$message_id = $_GET["par2"];
			$owner = getOwner($message_id);
			$user_id_for_permissions = $_GET["par3"];
			updateMessage($message_id, $message, $user_id_for_permissions, $owner);
		}
		
		header("location: messages.php");
	}
	?>
	
</body>
</html>

