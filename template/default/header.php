<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($SITE_TITLE) ?></title>
<link rel="stylesheet" href="templates/<?= htmlspecialchars(TEMPLATE) ?>/style.css">
</head>
<body>

<div id="topbg"></div>
<div id="nav">
	<div id="home"><?= htmlspecialchars($SITE_NAME) ?></div>

	<?php if ($LOGGED_IN): ?>
	<div id="left">
		<ul class="menutabs">
			<li class="home"><a href="index.php">Home</a></li>
			<li class="servers"><a href="server.php">My Servers</a></li>
		</ul>
	</div>
	<div id="right">
		<ul class="menutabs">
			<li class="account"><a href="profile.php">Account</a></li>
			<li class="logout"><a href="logout.php">Logout</a></li>
		</ul>
	</div>
	<div id="time"><?= date("l | F j, Y | g:i A") ?></div>
	<?php endif; ?>
</div>

<div id="page">
	<div id="content">
	<div id="container">