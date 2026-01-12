<?php

$connection = mysql_connect(DBHOST, DBUSER, DBPASSWORD);

if (!$connection) {
	http_response_code(500);
	exit(
		'<div style="border:1px dashed #CC0000;
					 font-family:Tahoma;
					 background-color:#FBEEEB;
					 width:100%;
					 padding:10px;
					 color:#CC0000;
					 text-align:center;">
			<b>Critical Error</b><br />
			Database connection failed.
		</div>'
	);
}

if (!mysql_select_db(DBNAME, $connection)) {
	http_response_code(500);
	exit(
		'<div style="border:1px dashed #CC0000;
					 font-family:Tahoma;
					 background-color:#FBEEEB;
					 width:100%;
					 padding:10px;
					 color:#CC0000;
					 text-align:center;">
			<b>Critical Error</b><br />
			Database selection failed.
		</div>'
	);
}