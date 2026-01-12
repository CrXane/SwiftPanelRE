<?php
declare(strict_types=1);

ini_set('max_execution_time', '120');
ini_set('output_buffering', 'Off');

$configPath = dirname(__DIR__) . '/configuration.php';
if (is_file($configPath)) {
 	require $configPath;
}

$mysqli = @mysqli_connect(DBHOST, DBUSER, DBPASSWORD, DBNAME);
if (!$mysqli) {
 	die('Database connection failed: ' . htmlspecialchars(mysqli_connect_error()));
}
mysqli_set_charset($mysqli, 'utf8mb4');

chdir('..');
$baseDir = getcwd();
chdir('install');

echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Swift Panel Install</title>
<link href="scripts/style.css" rel="stylesheet">
</head>
<body>
<div id="topbg"></div>
<div id="nav">
  <div id="home">Install Panel</div>
</div>
<div id="page">
<div id="content">
HTML;

$step = $_GET['step'] ?? 'one';

echo '<table class="title" width="100%"><tr><td><h1>';
echo match ($step) {
 	'two'   => 'Install Database',
 	'three' => 'Panel Installation',
 	default => 'Check Requirements',
};
echo '</h1></td></tr></table>';

if ($step === 'one') {
 	echo '<b>Step 1</b> > Step 2 > Step 3<br><br>';
 	$error = false;

 	$error |= status(is_file('../configuration.php'),
 	 	'Configuration File FOUND',
 	 	'Configuration File NOT FOUND',
 	 	'Edit <b>$baseDir/configuration-dist.php</b> and rename it'
 	);

 	$error |= status(function_exists('mysqli_connect'),
 	 	'DATABASE OK',
 	 	'NO MYSQLI',
 	 	'MySQLi extension required'
 	);

 	$error |= status(extension_loaded('ftp'),
 	 	'FTP OK',
 	 	'FTP MISSING',
 	 	'FTP extension recommended',
 	 	false
 	);

 	$error |= status(extension_loaded('curl'),
 	 	'CURL OK',
 	 	'CURL MISSING',
 	 	'Curl recommended',
 	 	false
 	);

 	if (!$error) {
 	 	echo '<center>
 	 	<form method="get">
 	 	 	<input type="hidden" name="step" value="two">
 	 	 	<input type="submit" class="button green" value="Next Step">
 	 	</form>
 	 	</center>';
 	} else {
 	 	echo '<center><b>Fix errors and retry.</b></center>';
 	}
}
elseif ($step === 'two') {
 	echo '<a href="index.php">Step 1</a> > <b>Step 2</b> > Step 3<br><br>';

 	$res = mysqli_query($mysqli, 'SHOW TABLES');
 	if (!$res) {
 	 	die('SHOW TABLES failed: ' . htmlspecialchars(mysqli_error($mysqli)));
 	}

 	$hasTables = mysqli_num_rows($res) > 0;

 	if ($hasTables) {
 	 	echo <<<HTML
<b>Existing database detected</b><br><br>
<form method="get">
<input type="hidden" name="step" value="three">
<label><input type="radio" name="version" value="full" checked> Panel Install</label><br><br>
<input type="submit" class="button red" value="Proceed">
</form>
HTML;
 	} else {
 	 	echo <<<HTML
No tables found.<br><br>
<form method="get">
<input type="hidden" name="step" value="three">
<input type="hidden" name="version" value="full">
<input type="submit" class="button green" value="Install Database">
</form>
HTML;
 	}
}
elseif ($step === 'three') {
 	$version = preg_replace('/[^a-z0-9_-]/i', '', ($_GET['version'] ?? 'full'));

 	echo '<b>Installing Database</b><br><br>';
 	echo '<span class="failed">Do not interrupt!</span><br>';

    runSqlFile($mysqli, "updates/full.sql");

 	echo <<<HTML
<br><b>Installation Complete</b><br><br>
<span class="failed">DELETE THE INSTALL FOLDER</span><br><br>
Admin Login: <a href="/admin">Admin Panel</a><br>
Username: <b>admin</b><br>
Password: <b>password</b>
HTML;
}

function status(bool $ok, string $pass, string $fail, string $msg = '', bool $fatal = true): bool
{
 	if ($ok) {
 	 	echo "<span class='ok'>[ $pass ]</span><br><br>";
 	 	return false;
 	}
 	echo "<span class='failed'>[ $fail ]</span><br>$msg<br><br>";
 	return $fatal;
}

function runSqlFile(mysqli $db, string $file): void
{
 	if (!is_file($file)) {
 	 	die("SQL file missing: " . htmlspecialchars($file));
 	}

 	$sql = file_get_contents($file);
 	if ($sql === false) {
 	 	die("Unable to read SQL file: " . htmlspecialchars($file));
 	}

 	$statements = array_filter(array_map('trim', preg_split('/;\s*\R/', $sql)));

 	foreach ($statements as $stmt) {
 	 	if ($stmt === '' || str_starts_with(ltrim($stmt), '--')) continue;
 	 	if (!mysqli_query($db, $stmt)) {
 	 	 	die("SQL error in " . htmlspecialchars($file) . ": " . htmlspecialchars(mysqli_error($db)));
 	 	}
 	}
}