<?php
	include("functions.php");
	if (isLoggedIn()) {
		logout();
	}
	header("location: index.php");
?>

