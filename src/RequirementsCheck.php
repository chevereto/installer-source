<?php

class RequirementsCheck
{
    /** @var Requirements */
    public $requirements;

    /** @var Runtime */
    public $runtime;

    /** @var array Missing table used for output [k => ['components' => %, 'message']] */
    public $missing;

    /** @var array Missing index used for internal awareness */
    public $missed;

    /** @var array Maps PHP extension name to its documentation identifier */
    const EXTENSIONS_MAP = array(
        'curl' => 'book.curl',
        'hash' => 'book.hash',
        'json' => 'book.json',
        'mbstring' => 'book.mbstring',
        'PDO' => 'book.pdo',
        'PDO_MYSQL' => 'ref.pdo-mysql',
        'session' => 'book.session',
        'zip' => 'book.zip',
    );

    /** @var array Maps PHP extension name to its documentation identifier */
    const CLASSES_MAP = array(
        'DateTime' => 'class.datetime',
        'DirectoryIterator' => 'class.directoryiterator',
        'Exception' => 'class.exception',
        'PDO' => 'class.pdo',
        'PDOException' => 'class.pdoexception',
        'RegexIterator' => 'class.regexiterator',
        'RecursiveIteratorIterator' => 'class.recursiveiteratoriterator',
        'ZipArchive' => 'class.ziparchive',
    );

    public function __construct(Requirements $requirements, Runtime $runtime)
    {
        $this->missing = array();
        $this->checkPHPVersion($requirements->phpVersions);
        $this->checkPHPProfile($requirements->phpExtensions, $requirements->phpClasses);
        $this->checkTimezone();
        $this->checkSessions();
        $this->checkWorkingPaths($runtime->workingPaths);
        $this->checkImageLibrary();
        $this->checkFileUploads();
        $this->checkApacheModRewrite();
        $this->checkUtf8Functions();
        $this->checkCurl();
        if (!$this->isMissing('cURL')) {
            $this->checkSourceAPI();
        }
    }

    public function checkPHPVersion(array $phpVersions)
    {
        if (version_compare(PHP_VERSION, $phpVersions[0], '<')) {
            $this->addMissing('PHP', 'https://php.net', 'Use a newer %l version (%c '.$phpVersions[0].' required, '.$phpVersions[1].' recommended)');
        }
    }

    public function checkPHPProfile(array $extensions, array $classes)
    {
        $core = array(
            'extensions' => array_intersect_key(static::EXTENSIONS_MAP, array_flip($extensions)),
            'classes' => array_intersect_key(static::CLASSES_MAP, array_flip($classes)),
        );

        $nouns = array(
            'extensions' => array('extension', 'extensions'),
            'classes' => array('class', 'classes'),
        );
        $core_check_function = array(
            'extensions' => array('get_loaded_extensions', 'extension_loaded'),
            'classes' => array('get_declared_classes', 'class_exists'),
        );
        foreach ($core as $type => $array) {
            $n = $nouns[$type];
            $core_check = $core_check_function[$type];
            $missing = array();
            $loaded = @$core_check[0]();
            if ($loaded) {
                foreach ($loaded as $k => &$v) {
                    $v = strtolower($v);
                }
            } else {
                $function = create_function('$var', 'return @'.$core_check[1].'($var);');
            }
            foreach ($array as $k => $v) {
                if (($loaded && !in_array(strtolower($k), $loaded)) || ($function && $function($k))) {
                    $missing['c'][] = $k;
                    $missing['l'][] = 'http://www.php.net/manual/'.$v.'.php';
                }
            }
            if ($missing) {
                $l = array();
                $c = array();
                $message = 'Enable %l PHP <b>%n</b>';
                if (count($missing['c']) == 1) {
                    $missing_strtr = array('%n' => $n[0]);
                } else {
                    foreach ($missing['l'] as $k => $v) {
                        $l[] = '%l'.$k;
                    }
                    $last = array_pop($l);
                    $missing_strtr['%l'] = implode(', ', $l).' and '.$last;
                    $missing_strtr['%n'] = $n[1];
                }
                $message = strtr($message, $missing_strtr);
                $this->addMissing($missing['c'], $missing['l'], $message);
            }
        }
    }

    public function checkTimezone()
    {
        if (function_exists('date_default_timezone_get')) {
            $tz = @date_default_timezone_get();
            $dtz = @date_default_timezone_set($tz);
            if (!$dtz && !@date_default_timezone_set('America/Santiago')) {
                $this->addMissing(array('timezone', 'date.timezone'), array('http://php.net/manual/en/timezones.php', 'http://php.net/manual/en/datetime.configuration.php#ini.date.timezone'), '<b>'.$tz.'</b> is not a valid %l0 identifier in %l1');
            }
        }
    }

    public function checkSessions()
    {
        $session_link = 'http://php.net/manual/en/book.session.php';
        if (session_status() == PHP_SESSION_DISABLED) {
            $this->addMissing('sessions', $session_link, 'Enable %l support (session_start)');
        }
        $session_save_path = @realpath(@session_save_path());
        if ($session_save_path) {
            if (!is_writable($session_save_path)) {
                $session_errors[] = $k;
            }
            if (isset($session_errors)) {
                $this->addMissing(array('session', 'session.save_path'), array($session_link, 'http://php.net/manual/en/session.configuration.php#ini.session.save-path'), str_replace('%s', implode('/', $session_errors), 'Missing PHP <b>%s</b> permission in <b>'.$session_save_path.'</b> (%l1)'));
            }
        }
        $_SESSION['chevereto-installer'] = true;
        if (!$_SESSION['chevereto-installer']) {
            $this->addMissing('sessions', $session_link, 'Any server setting related to %l support (%c are not working)');
        }
    }

    public function checkWorkingPaths(array $workingPaths)
    {
        $rw_fn = array('read' => 'is_readable', 'write' => 'is_writeable');
        foreach ($workingPaths as $var) {
            foreach (array('read', 'write') as $k => $v) {
                if (!@$rw_fn[$v]($var)) {
                    $permissions_errors[] = $k;
                }
            }
            if (isset($permissions_errors)) {
                $error = implode('/', $permissions_errors);
                $component = $var.' '.$error.' permission'.(count($permissions_errors) > 1 ? 's' : null);
                $message = 'No PHP <b>'.$error.'</b> permission in <b>'.$var.'</b>';
                $this->addMissing($component, null, $message);
                unset($permissions_errors);
            }
        }
    }

    public function checkImageLibrary()
    {
        if (!@extension_loaded('gd') && !function_exists('gd_info')) {
            $this->addMissing('GD Library', 'http://php.net/manual/en/book.image.php', 'Enable %l');
        } else {
            foreach (array('PNG', 'GIF', 'JPG', 'WBMP') as $k => $v) {
                if (!imagetypes() & constant('IMG_'.$v)) {
                    $this->addMissing('GD Library', 'http://php.net/manual/en/book.image.php', 'Enable %l '.$v.' image support');
                }
            }
        }
    }

    public function checkFileUploads()
    {
        if (!ini_get('file_uploads')) {
            $this->addMissing('file_uploads', 'http://php.net/manual/en/ini.core.php#ini.file-uploads', 'Enable %l (needed for file uploads)');
        }
    }

    public function checkApacheModRewrite()
    {
        if (isset($_SERVER['SERVER_SOFTWARE']) && preg_match('/apache/i', $_SERVER['SERVER_SOFTWARE']) && function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules())) {
            $this->addMissing('mod_rewrite', 'http://httpd.apache.org/docs/current/mod/mod_rewrite.html', 'Enable %l (needed for URL rewriting)');
        }
    }

    public function checkUtf8Functions()
    {
        $utf8_errors = array();
        foreach (array('utf8_encode', 'utf8_decode') as $v) {
            if (!function_exists($v)) {
                $utf8_errors['c'][] = $v;
                $utf8_errors['l'][] = 'http://php.net/manual/en/function.'.str_replace('_', '-', $v).'.php';
            }
        }
        if ($utf8_errors) {
            $this->addMissing($utf8_errors['c'], $utf8_errors['l'], count($utf8_errors['c']) == 1 ? 'Enable %l function' : 'Enable %l0 and %l1 functions');
        }
    }

    public function checkCurl()
    {
        if (!function_exists('curl_init')) {
            $this->addMissing('cURL', 'http://php.net/manual/en/book.curl.php', 'Enable PHP %l');
        }
    }

    public function checkSourceAPI()
    {
        $headers = @get_headers(VENDOR['apiUrl'], 1);
        if ($headers) {
            $http_statusCode = substr($headers[0], 9, 3);
            if ($http_statusCode != 200) {
                $http_error_link = '<a href="https://en.wikipedia.org/wiki/HTTP_'.$http_statusCode.'" target="_blank">HTTP '.$http_statusCode.'</a>';
                $this->addMissing('Chevereto API', VENDOR['apiUrl'], "An $http_error_link error occurred when trying to connect to %l");
            }
        } else {
            $api_parse_url = parse_url(VENDOR['apiUrl']);
            $api_offline_link = '<a href="https://isitdownorjust.me/'.$api_parse_url['host'].'" target="_blank">offline</a>';
            $this->addMissing('Chevereto API', VENDOR['apiUrl'], "Can't connect to %l. Check for any outgoing network blocking or maybe our server is $api_offline_link at this time");
        }
    }

    protected function addMissing()
    {
        //$components, $links, $msgtpl
        $args = func_get_args();
        $placeholders = array();
        foreach (array('c', 'l') as $k => $v) {
            $key = '%'.$v;
            if (gettype($args[$k]) == 'string') {
                $args[$k] = array($args[$k]);
            }
            if (gettype($args[$k]) == 'string' || count($args[$k]) == 1) {
                $args[2] = str_replace($key, $key.'0', $args[2]);
            }
            if (is_array($args[$k])) {
                foreach ($args[$k] as $k_ => $v_) {
                    if ($v == 'l') {
                        $v_ = '<a href="'.$args[1][$k_].'" target="_blank">'.$args[0][$k_].'</a>';
                    }
                    $placeholders[$key.$k_] = $v_;
                }
            }
        }
        $message = strtr($args[2], $placeholders);
        $this->missing[] = array(
            'components' => $args[0],
            'message' => $message,
        );
        foreach ($args[0] as $k => $v) {
            $this->missed[] = $v;
        }
    }

    /**
     * @return bool
     */
    public function isMissing(string $key)
    {
        return is_array($this->missed) ? in_array($key, $this->missed) : false;
    }
}
