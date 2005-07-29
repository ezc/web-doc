<?php
/* $Id$ */

include '../include/init.inc.php';
require_once '../include/lib_auth.inc.php';

auth();
if (!is_admin()) {
	die('you are not an admin!');
}

ob_start(); // hack for phpinfo()
echo site_header('Admin Zone');


// helper functions

function info() {
	ob_end_clean(); //get ride of the headers
	phpinfo();
	exit;
}


function print_file_list($base)
{
	if (!empty($_GET['file']) && !is_dir(dirname(__FILE__) . "/../$_GET[file]"))
		return;

	$files = glob(dirname(__FILE__) . '/../' .  @$_GET['file'] . $base);
	$uri   = preg_replace('/&file=[^&]*/', '', $_SERVER['REQUEST_URI']);
	if ($files) {
		echo '<p>Available files:</p><ul>';
		foreach ($files as $file) {
			$file = basename($file);
			if ($file == 'CVS') continue;
			echo "<li><a href='$uri&file=". urlencode(@$_GET['file'] . "/$file")  . "'>$file</a></li>";
		}
		echo '</ul>';
	} else {
		echo '<p>There are no files currently available</p>';
	}

}

function sql()
{
	if (empty($_POST['command'])) {
		sql_print_textarea('', @$_REQUEST['file']);

	// execute the sql
	} else {
		$idx = sqlite_open(dirname(__FILE__) . '/../sqlite/' . $_POST['file'], 0666, $error);
		if (!$idx) {
			echo "<p>$error</p>";
			sql_print_textarea($_POST['command'], $_POST['file']);
			return;
		}

		$result = @sqlite_query($idx, $_POST['command']);
		if (!$result) {
			echo '<p><strong>There was an error in the query:</strong> ' . sqlite_error_string(sqlite_last_error($idx)) . '</p><p>&nbsp;</p>';
			sql_print_textarea($_POST['command'], $_POST['file']);
			return;
		}

		echo '<p>Affected rows: ' . sqlite_changes($idx) , '</p>';
		echo '<pre>' . htmlspecialchars(print_r(sqlite_fetch_all($result), true)) . '</pre>';
		sqlite_close($idx);
	}
}


function sql_print_textarea($txt, $file)
{
	print_file_list('sqlite/*.sqlite');

	echo <<< HTML
<p>&nbsp;</p>
<form method="POST" action="$_SERVER[REQUEST_URI]">
 <p>SQL: <textarea name="command" rows="5" cols="70">$txt</textarea></p>
 <p>DB: <input type="text" name="file" value="$file" /></p>
 <p><input type="submit" value="Execute" /></p>
</form>
HTML;

}


function chmodf()
{
	if (empty($_POST['mod']) || empty($_REQUEST['file'])) {
		rmch_print_html(@$_REQUEST['file'], @$_POST['mod'], true);

	// change the permissions
	} else {
		$path = realpath(dirname(__FILE__) . "/../$_POST[file]");
		$allowed = dirname(dirname(__FILE__));

		if (strncmp($path, $allowed, strlen($allowed))) {
			echo "<p>The file isn't within an allowed directory!</p>";
			return;
		}

		if (chmod($path, octdec($_POST['mod'])))
			echo '<p>chmod() ok!</p>';
		else
			echo '<p>chmod() failed!</p>';
	}
}


function rm()
{
	if (empty($_REQUEST['file'])) {
		rmch_print_html(@$_REQUEST['file'], '', false);

	// change the permissions
	} else {
		$path = realpath(dirname(__FILE__) . "/../$_REQUEST[file]");
		$allowed = dirname(dirname(__FILE__));

		if (strncmp($path, $allowed, strlen($allowed))) {
			echo "<p>The file isn't within an allowed directory!</p>";
			return;
		}

		if (unlink($path))
			echo '<p>unlink() ok!</p>';
		else
			echo '<p>unlink() failed!</p>';
	}
}


function rmch_print_html($file, $val, $mod)
{
	print_file_list('/*');

	echo <<< HTML
<p>&nbsp;</p>
<form method="POST" action="$_SERVER[REQUEST_URI]">
 <p>File: <input type="text" name="file" value="$file" /></p>
HTML;

	if ($mod)
 		echo '<p>Permissions: <input type="text" name="mod" value="' . $val . '" /></p>';

	echo <<< HTML
 <p><input type="submit" value="Execute" /></p>
</form>
HTML;

}


// control flow
if (empty($_GET['z'])) {

	echo <<< HTML
<p>Menu:</p>
<ul>
 <li><a href="?z=sql">SQL Injector</a></li>
 <li><a href="?z=chmodf">chmod</a></li>
 <li><a href="?z=rm">remove files</a></li>
 <li><a href="?z=info">PHP info</a></li>
</ul>
HTML;

} else {
	switch ($_GET['z']) {
		case 'sql':
		case 'chmodf':
		case 'rm':
		case 'info':
			$_GET['z']();
			break;

		default:
			echo '<p>wrong zone!</p>';
	}
}


echo site_footer();
?>