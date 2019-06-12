<?php
/* --------------------------------------------------------------------

    Chevereto Installer
    http://chevereto.com/

    @version 2.0.0
    @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

      /$$$$$$  /$$                                                           /$$
     /$$__  $$| $$                                                          | $$
    | $$  \__/| $$$$$$$   /$$$$$$  /$$    /$$ /$$$$$$   /$$$$$$   /$$$$$$  /$$$$$$    /$$$$$$
    | $$      | $$__  $$ /$$__  $$|  $$  /$$//$$__  $$ /$$__  $$ /$$__  $$|_  $$_/   /$$__  $$
    | $$      | $$  \ $$| $$$$$$$$ \  $$/$$/| $$$$$$$$| $$  \__/| $$$$$$$$  | $$    | $$  \ $$
    | $$    $$| $$  | $$| $$_____/  \  $$$/ | $$_____/| $$      | $$_____/  | $$ /$$| $$  | $$
    |  $$$$$$/| $$  | $$|  $$$$$$$   \  $/  |  $$$$$$$| $$      |  $$$$$$$  |  $$$$/|  $$$$$$/
     \______/ |__/  |__/ \_______/    \_/    \_______/|__/       \_______/   \___/   \______/

  --------------------------------------------------------------------- */

declare(strict_types=1);

/* --- Begins: Dev editable --- */
const APP_NAME = 'Chevereto Installer';
const APP_VERSION = '2.0.0';
const APP_URL = 'https://github.com/Chevereto/Installer';

const PHP_VERSION_MIN = '7.0';
const PHP_VERSION_RECOMMENDED = '7.2';

const VENDOR = [
    'name' => 'Chevereto',
    'url' => 'https://chevereto.com',
    'apiUrl' => 'https://chevereto.com/api',
];

const APPLICATIONS = [
    'chevereto' => [
        'name' => 'Chevereto',
        'license' => 'Paid',
        'url' => 'https://chevereto.com',
        'zipball' => 'https://chevereto.com/api/download/latest',
        'folder' => 'chevereto',
        'vendor' => VENDORS['chevereto'],
    ],
    'chevereto-free' => [
        'name' => 'Chevereto-Free',
        'license' => 'Open Source',
        'url' => 'https://github.com/Chevereto/Chevereto-Free',
        'zipball' => 'https://api.github.com/repos/Chevereto/Chevereto-Free/zipball',
        'folder' => 'Chevereto-Chevereto-Free-',
        'vendor' => VENDORS['chevereto'],
    ],
];

$phpSettings = [
    'error_reporting' => E_ALL ^ E_NOTICE,
    'log_errors' => '1',
    'display_errors' => '1',
    'error_log' => __DIR__.'/installer.error.log',
    'time_limit' => 0,
    'default_charset' => 'utf-8',
    'LC_ALL' => 'en_US.UTF8',
];

$phpExtensions = [
    'curl',
    'hash',
    'json',
    'mbstring',
    'PDO',
    'PDO_MYSQL',
    'session',
];

$phpClasses = [
    'DateTime',
    'DirectoryIterator',
    'Exception',
    'PDO',
    'PDOException',
    'RegexIterator',
    'RecursiveIteratorIterator',
    'ZipArchive',
];

$themeColor = '#ecf0f1';

/* --- Ends: Dev editable --- */

const INSTALLER_FILEPATH = __FILE__;

include 'src/dump.php';
include 'src/password.php';
include 'src/Installer.php';
include 'src/Logger.php';
include 'src/Requirements.php';
include 'src/RequirementsCheck.php';
include 'src/Runtime.php';
include 'src/Cpanel.php';
include 'src/Output.php';
include 'src/processAction.php';

// $cpanel = new Cpanel('chevereto', ']2YdOVytq@7A');
// $createDb = $cpanel->setupMysql();
// dump($createDb);

$logger = new Logger(APP_NAME.' '.APP_VERSION);

$requirements = new Requirements([PHP_VERSION_MIN, PHP_VERSION_RECOMMENDED]);
$requirements->setPHPExtensions($phpExtensions);
$requirements->setPHPClasses($phpClasses);

$runtime = new Runtime($logger);
$runtime->setSettings($phpSettings);
// $runtime->setServer([
//     'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
//     'HTTPS' => 'On',
//     'SERVER_SOFTWARE' => 'php-cli',
//     'SERVER_PROTOCOL' => 'PHP/CLI',
//     'HTTP_HOST' => 'php-cli',
//     'HTTP_X_FORWARDED_PROTO' => null,
// ]);
$runtime->run();

$requirementsCheck = new RequirementsCheck($requirements, $runtime);

if (isset($_REQUEST['action'])) {
    // if ($requirementsCheck->missing) {
    //     $Output = new Output();
    //     $missing = array();
    //     foreach ($requirementsCheck->missing as $k => $v) {
    //         $missing[] = $v;
    //     }
    //     $Output->addData('missing', $missing);
    //     $Output->setHttpStatus(500);
    //     $Output->setResponse('Missing server requirements', 500);
    //     $Output->exec();
    // }
    // $processAction = new processAction();
}

$pageId = $requirementsCheck->missing ? 'error' : 'install';

if ($pageId == 'install' && !isset($_REQUEST['UpgradeToPaid']) && preg_match('/nginx/i', $runtime->serverSoftware)) {
    $nginx = '<p>Make sure to add the following rules to your <a href="https://www.digitalocean.com/community/tutorials/understanding-the-nginx-configuration-file-structure-and-configuration-contexts" target="_blank">nginx.conf</a> server block. Restart the server to apply changes. Once done, come back here and continue the process.</p>
<textarea class="pre" ondblclick="this.select()">#Chevereto: Disable access to sensitive files
location ~* '.$runtime->relPath.'(app|content|lib)/.*\.(po|php|lock|sql)$ {
	deny all;
}
#Chevereto: CORS headers
location ~* '.$runtime->relPath.'.*\.(ttf|ttc|otf|eot|woff|woff2|font.css|css|js) {
	add_header Access-Control-Allow-Origin "*";
}
#Chevereto: Upload path for image content only and set 404 replacement
location ^~ '.$runtime->relPath.'images/ {
	location ~* (jpe?g|png|gif) {
		log_not_found off;
		error_page 404 '.$runtime->relPath.'content/images/system/default/404.gif;
	}
	return 403;
}
#Chevereto: Pretty URLs
location '.$runtime->relPath.' {
	index index.php;
	try_files $uri $uri/ '.$runtime->relPath.'index.php?$query_string;
}</textarea>';
}

/** @var string a data-64 PNG image */
$shortcutIcon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACABAMAAAAxEHz4AAAAMFBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABaPxwLAAAAD3RSTlMAECAwQGBwgI+fr7/P3+/Lm0b7AAABXElEQVR4Xu3WP0oDQRiG8dWN6vq/tLQTu21sJYUHEDyAHkHs7PQGwcYyHsFeBG8Qe4sIHiA2goFkHxlMeMOGwMyXLn5vtZnix5OFhcn+w3yN5nzAUmewNxewCpdzAWtw5YADDjjggAMOOOCAA7U54IADDhyX00B+Hg+s8DMNtDmLBnagVQcK+IgGdqFfB9rwFQ2sM0oQUAC3WfTa0Begk+gVQEuADpISBOh3WoIABSQlCFBAakIAFGBICIACDAkBUIAhIQAKMCQEQAGGhAAowJDwADcKMCRU0FOAJQEUYEoABRgSFGBOUIA9wR6gz9C+QgHWhIiATWjOTpgd0IAnAYYtHuCAAw5swKXxHv04fnizANvjK2AO3xbgAso/qktVpgN5j6Goz3TgROFbwH0qcAS8joDlHvD+nLQXoDpQjWn633kXw4YTb34fw+6yiR12SNzguvZWTxOXLdJ8v+uv3HMGDU3uAAAAAElFTkSuQmCC';

/** @var string a SVG inline element */
$doctitle = APP_NAME;
$css = file_get_contents('html/style.css');
$scripts = file_get_contents('html/scripts.js');
$script = file_get_contents('html/script.js');
$svgLogo = file_get_contents('html/logo.svg');
$jsVars = [
    'rootUrl' => $runtime->rootUrl,
    'installerFile' => $runtime->installerFilename,
    'serverStr' => $runtime->serverString,
];
ob_start();
require 'template/content.php';
$content = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en" id="<?php echo $pageId; ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no,maximum-scale=1"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="theme-color" content="<?php echo $themeColor; ?>"/>
  <title><?php echo $doctitle; ?></title>
  <link rel="shortcut icon" type="image/png" href="<?php echo $shortcutIcon; ?>"/>
  <style><?php echo $css; ?></style>
  <script>vars = <?php echo json_encode($jsVars); ?></script>
</head>
<body class="body--flex">
  <main>
<?php echo $content; ?>
  </main>
  <script>
<?php echo $scripts; ?>
  </script>
  <script>
<?php echo $script; ?>
  </script>
</body>
</html>