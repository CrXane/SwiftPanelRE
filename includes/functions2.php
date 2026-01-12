<?php

function queryMultipleServers(array $servers): void
{
	require_once 'query.php';

	$query = new Query();
	$query->addServers($servers);
	$results = $query->requestData();

	print_r($results);
}

function querySingleServer(array $server): ?array
{
	require_once 'query.php';

	$query = new Query();
	$query->addServer(1, $server);
	$results = $query->requestData()[1] ?? null;

	if (!$results || empty($results['gq_online'])) {
		return null;
	}

	return $server[0] === 'valve'
		? formatValveQueryResult($results)
		: formatGenericQueryResult($results);
}

function formatValveQueryResult(array $r): array
{
	return [
		'Server Name'	   => $r['gq_hostname'],
		'Current Map'	   => $r['gq_mapname'],
		'Game Description' => $r['game_descr'],
		'Players'		   => $r['gq_numplayers'] - $r['num_bots'],
		'Bot Players'	   => $r['num_bots'],
		'Max Players'	   => $r['gq_maxplayers'],
		'Server Type'	   => match ($r['gq_dedicated']) {
			'd' => 'Dedicated',
			'l' => 'Listen',
			'p' => 'SourceTV',
			default => 'Unknown',
		},
		'Server OS'		 => match ($r['os']) {
			'w' => 'Windows',
			'l' => 'Linux',
			default => 'Unknown',
		},
		'Password'		  => $r['gq_password'] ? 'Protected' : 'Not Set',
		'VAC Secure'		=> $r['secure'] ? 'Yes' : 'No',
	];
}

function formatGenericQueryResult(array $r): array
{
	return [
		'Server Name'  => preg_replace('/\^[0-9]/', '', $r['gq_hostname']),
		'Current Map'  => $r['gq_mapname'],
		'Players'	  => $r['gq_numplayers'],
		'Max Players'  => $r['gq_maxplayers'],
		'Game Type'	=> $r['gq_gametype'],
		'Dedicated'	=> $r['gq_dedicated'] ? 'Yes' : 'No',
		'Password'	 => $r['gq_password'] ? 'Protected' : 'Not Set',
	];
}

function querySourceEngineUdp(string $ip, int $port): array
{
	$sock = @fsockopen("udp://{$ip}", $port, $errno, $errstr, 2);
	if (!$sock) {
		return [];
	}

	stream_set_timeout($sock, 2);
	fwrite($sock, "\xFF\xFF\xFF\xFFTSource Engine Query");
	$data = fread($sock, 4096);
	fclose($sock);

	if (!$data) {
		return [];
	}

	$data = substr($data, 4);
	$parts = explode("\x00", $data);

	return [
		'servername' => $parts[0] ?? '',
		'map'		=> $parts[1] ?? '',
		'players'	=> $parts[2] ?? '',
	];
}

function queryQuakeUdp(string $ip, int $port): array
{
	$sock = @fsockopen("udp://{$ip}", $port, $errno, $errstr, 2);
	if (!$sock) {
		return [];
	}

	stream_set_timeout($sock, 2);
	fwrite($sock, "\xFF\xFF\xFF\xFFgetinfo");
	$data = fread($sock, 4096);
	fclose($sock);

	if (!$data) {
		return [];
	}

	$lines = explode("\n", $data);
	$vars  = explode("\\", $lines[1] ?? '');

	return [
		'Server Name'  => preg_replace('/\^./', '', $vars[4] ?? ''),
		'Current Map'  => $vars[6] ?? '',
		'Players'	  => $vars[8] ?? '',
		'Max Players'  => $vars[10] ?? '',
	];
}

function queryQuakeUdpCompact(string $ip, int $port): array
{
	$sock = @fsockopen("udp://{$ip}", $port, $errno, $errstr, 2);
	if (!$sock) {
		return [];
	}

	stream_set_timeout($sock, 2);
	fwrite($sock, "\xFF\xFF\xFF\xFFgetinfo");
	$data = fread($sock, 4096);
	fclose($sock);

	if (!$data) {
		return [];
	}

	$lines = explode("\n", $data);
	$vars  = explode("\\", $lines[1] ?? '');

	return [
		'servername' => preg_replace('/\^./', '', $vars[4] ?? ''),
		'map'		=> $vars[6] ?? '',
		'players'	=> "<b>{$vars[8]}</b> Players / <b>{$vars[10]}</b> Slots",
	];
}