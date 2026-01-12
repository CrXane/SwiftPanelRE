<?php

function get_ftp_connection($hostname, $port, $username, $password, $passive = TRUE)
{
	if(FALSE === ($ftp_result = @ftp_connect($hostname, $port, 15))) {
		$_SESSION["msg1"] = "FTP Connection Failed!";
		$_SESSION["msg2"] = "Could not connect to FTP.";
		return FALSE;
	}
	if(!get_ftp_login($ftp_result, $username, $password)) {
		$_SESSION["msg1"] = "FTP Connection Failed!";
		$_SESSION["msg2"] = "Could not login to FTP.";
		return FALSE;
	}
	if($passive == TRUE) {
		ftp_pasv($ftp_result, TRUE);
	}
	return $ftp_result;
}

function get_ftp_login($conn_id, $username, $password)
{
	return @ftp_login($conn_id, $username, $password);
}

function normalizePath(string $path): string
{
	if (str_starts_with($path, '/')) {
		$path = ltrim($path, '/');
		return rtrim($path, '/') . '/';
	}

	if (str_ends_with($path, '/')) {
		$path = rtrim($path, '/');
		return '/' . $path;
	}

	return $path;
}

function buildFtpBreadcrumb(string $path): string
{
	global $serverid;

	$breadcrumb = '';
	$path = rtrim($path, '/');

	if ($path === '') {
		return '';
	}

	while ($path !== '') {
		$parts = explode('/', $path);
		$current = end($parts);

		$breadcrumb =
			" > <a href='serverftp.php?id={$serverid}&path=" .
			urlencode($path . '/') .
			"'>" .
			htmlspecialchars($current, ENT_QUOTES, 'UTF-8') .
			"</a>" .
			$breadcrumb;

		if (!str_contains($path, '/')) {
			break;
		}

		$path = substr($path, 0, strrpos($path, '/'));
	}

	return $breadcrumb;
}

function permsToChmod(string $mode): string
{
	$mode = preg_replace('/[^rwx-]/', '', $mode ?? '');
	$mode = str_pad(substr($mode, 0, 9), 9, '-');

	$map = ['r' => 4, 'w' => 2, 'x' => 1, '-' => 0];

	$d1 = $map[$mode[0]] + $map[$mode[1]] + $map[$mode[2]];
	$d2 = $map[$mode[3]] + $map[$mode[4]] + $map[$mode[5]];
	$d3 = $map[$mode[6]] + $map[$mode[7]] + $map[$mode[8]];

	return (string)$d1 . (string)$d2 . (string)$d3;
}

function formatBytesIEC(int|float $bytes): string
{
	$units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
	$i = 0;

	while ($bytes >= 1024 && $i < count($units) - 1) {
		$bytes /= 1024;
		$i++;
	}

	return round($bytes, 2) . ' ' . $units[$i];
}

function getFtpItemType(string $perms): string
{
	return match ($perms[0] ?? '') {
		'd' => 'folder',
		'l' => 'link',
		default => 'file',
	};
}

function makeFtpFileLink(string $file): string
{
	global $serverid, $path;

	$editableExtensions = [
		'.txt', '.cfg', '.con', '.ini', '.log', '.int', '.inf',
		'.vdf', '.db', '.rtf', '.scriptcfg', '.mapcycle',
		'.rc', '.php', '.dem', '.sma', '.inc'
	];

	foreach ($editableExtensions as $ext) {
		if (str_ends_with(strtolower($file), $ext)) {
			return "<a href='serverftp.php?id={$serverid}&path=" .
				urlencode($path) .
				"&file=" . urlencode($file) .
				"'>" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "</a>";
		}
	}

	return htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
}

function ftpList(string $connection, string $path = '.'): array|false
{
	return ftp_nlist($connection, $path);
}

function ftpDeleteFile($connection, string $filepath): bool
{
	return ftp_delete($connection, $filepath);
}

function ftpDeleteRecursive($connection, string $filepath): bool
{
	$filepath = rtrim($filepath, '/') . '/';
	$list = ftp_nlist($connection, $filepath);

	if ($list !== false) {
		foreach ($list as $item) {
			if (!ftp_delete($connection, $item)) {
				ftpDeleteRecursive($connection, $item);
			}
		}
	}

	return ftp_rmdir($connection, $filepath);
}

function ftpEnsurePath($connection, string $path): bool
{
	if ($path === '' || !$connection) {
		return false;
	}

	$parts = explode('/', trim($path, '/'));
	$current = '';

	foreach ($parts as $part) {
		$current .= '/' . $part;
		if (!@ftp_chdir($connection, $current)) {
			ftp_chdir($connection, '/');
			if (!@ftp_mkdir($connection, $current)) {
				return false;
			}
		}
	}

	return true;
}

function ftpDownloadFile($connection, string $remotePath, string $filename): void
{
	$temp = fopen('php://temp', 'r+');
	ftp_fget($connection, $temp, $remotePath, FTP_BINARY);
	rewind($temp);

	header('Pragma: public');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="' . basename($filename) . '"');

	fpassthru($temp);
	fclose($temp);
	exit;
}