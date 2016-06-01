<html>
<head>
	<style>
		table, th, td {
			border: 1px solid black;
		    border-collapse: collapse;
		    width: 50%;
		}
		th, td {
		    padding: 15px;
		}
	</style>
</head>
<body>
	<?php
		$messages = getAllMessages();
		
		if ($messages == null) {
			echo "Brak wiadomości";
		} else {
	?>
		<table border="1">
			<tr>
				<th>Właściciel</th>
				<th>Wiadomość</th>
				<th>Ostatnia modyfikacja</th>
				<th>Akcja</th>
			</tr>
	<?php
			while($row = $messages->fetch_assoc()) {
				$message_id = $row["message_id"];
				$message = $row["text"];
				$modified = $row["modified"];
				$username_for_message = getUsernameForMessage($message_id);
				
				if (!isAllowedToEdit($message_id, $user_id)) {
					$disabled_edit = "disabled";
				} else {
					$disabled_edit = "";
				}
				
				$owner = getOwner($message_id);
				if ($user_id != $owner) {
					$disabled_deletion = "disabled";
				} else {
					$disabled_deletion = "";
				}
			
				echo "<tr>";
				echo "<td>" . $username_for_message . "</td>";
				echo "<td>" . $message . "</td>";
				echo "<td>" . $modified . "</td>";
		
				echo "<td>";
				echo "<form action='messages.php' method='get'>
						<input type='text' name='par' value='$message_id' hidden />
						<input type='submit' name='action' value='Edytuj' " . $disabled_edit . " />
					  </form>";
				echo "<form action='messages.php' method='get'>
						<input type='text' name='par' value='$message_id' hidden />
						<input type='submit' name='action' value='Usuń' " . $disabled_deletion . " />
					  </form>";
				echo "</td>";
				echo "</tr>";
			}
		}
	?>
	</table>
</body>
</html>

