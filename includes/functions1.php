<?php

function sanitizeInput(string $data): string
{
	$data = htmlentities($data, ENT_NOQUOTES, 'UTF-8');
	$data = str_replace(['#', '%'], ['&#35;', '&#37;'], $data);

	if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
		$data = stripslashes($data);
	}

	$data = trim($data);

	if (function_exists('mysql_real_escape_string')) {
		global $connection;
		$data = mysql_real_escape_string($data, $connection);
	} else {
		$data = mysql_escape_string($data);
	}

	return $data;
}

function formatDate(string $date, int $style = 0): string
{
	if (
		trim($date) === '' ||
		$date === '0000-00-00' ||
		$date === '0000-00-00 00:00:00' ||
		$date === 'Never'
	) {
		return 'Never';
	}

	if ($style === 0) {
		$timestamp = strtotime($date);
		return strlen($date) === 10
			? date('M j, Y', $timestamp)
			: date('M j, Y | g:i A', $timestamp);
	}

	if ($style === 1) {
		$timestamp = strtotime($date);
		return date('n/j/Y', $timestamp);
	}

	return 'Never';
}

function generateRandomString(int $len): string
{
	$chars = '0123456789bcdfghjkmnpqrstvwxyz';
	$out = '';

	while (strlen($out) < $len) {
		$c = $chars[random_int(0, strlen($chars) - 1)];
		$out .= $c;

		if (is_numeric($out)) {
			$out = '';
		}
	}

	return $out;
}

function buildStartCommand(array $row, string $ip, bool $escape = false): string
{
	$cmd = html_entity_decode($row['startline'], ENT_NOQUOTES, 'UTF-8');
	$cmd = str_replace(['&#35;', '&#37;'], ['#', '%'], $cmd);

	$niceMap = [
		'Very High'   => -18,
		'High'		=> -12,
		'Above Normal'=> -6,
		'Normal'	  => 0,
		'Below Normal'=> 6,
		'Low'		 => 12,
		'Very Low'	=> 18,
	];

	$nice = '';
	if ($row['priority'] !== 'None' && isset($niceMap[$row['priority']])) {
		$nice = 'nice -n ' . $niceMap[$row['priority']];
	}

	if (!str_contains($cmd, '{nice}')) {
		$cmd = '{nice} ' . $cmd;
	}

	$search = [
		'{ip}', '{port}', '{slots}',
		'{cfg1}', '{cfg2}', '{cfg3}', '{cfg4}',
		'{cfg5}', '{cfg6}', '{cfg7}', '{cfg8}',
		'{user}', '{homedir}', '{nice}'
	];

	$replace = [
		$ip,
		$row['port'],
		$row['slots'],
		$row['cfg1'], $row['cfg2'], $row['cfg3'], $row['cfg4'],
		$row['cfg5'], $row['cfg6'], $row['cfg7'], $row['cfg8'],
		$row['user'],
		$row['homedir'],
		$nice
	];

	$cmd = str_replace($search, $replace, $cmd);

	if ($escape) {
		$cmd = htmlentities($cmd, ENT_NOQUOTES, 'UTF-8');
		$cmd = str_replace(['#', '%'], ['&#35;', '&#37;'], $cmd);
	}

	return trim($cmd);
}

function formatStatusText(string $status): string
{
	return match ($status) {
		'Active', 'Online', 'Started'
			=> "<font color=\"#669933\"><b>{$status}</b></font>",
		'Inactive', 'Pending'
			=> "<font color=\"#FFAA00\"><b>{$status}</b></font>",
		'Suspended', 'Offline', 'Stopped'
			=> "<font color=\"#DD0000\"><b>{$status}</b></font>",
		default => $status,
	};
}

function formatStatusIcon(string $status): string
{
	$color = match ($status) {
		'Active', 'Online', 'Started' => 'green',
		'Inactive', 'Pending'		 => 'yellow',
		'Suspended', 'Offline', 'Stopped' => 'red',
		default => null,
	};

	if (!$color) {
		return '';
	}

	return "<img src=\"templates/default/images/status/{$color}.png\" width=\"25\" height=\"25\" align=\"middle\" alt=\"{$status}\" />";
}

function dbCount(string $query): int
{
	$result = mysql_query($query);
	$count = mysql_num_rows($result);
	mysql_free_result($result);
	return $count;
}

function dbRow(string $query, bool $allowEmpty = false): array
{
	$result = mysql_query($query);

	if (!$allowEmpty && mysql_num_rows($result) === 0) {
		mysql_free_result($result);
		echo '<p><b>No Results Found.</b></p>';
		exit;
	}

	$row = mysql_fetch_assoc($result) ?: [];
	mysql_free_result($result);
	return $row;
}

function dbQuery(string $query)
{
	return mysql_query($query);
}

function dbExec(string $query): void
{
	mysql_query($query);
}

function isInternetExplorer(int $version = 0): bool
{
	$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
	$pos = strpos($ua, 'MSIE ');

	if ($pos === false) {
		return false;
	}

	if ($version > 0) {
		$v = (int)substr($ua, $pos + 5, 1);
		return $v === $version;
	}

	return true;
}