<?php

class Controller
{
    /** @var array */
    public $params;

    /** @var string */
    public $response;

    /** @var array */
    public $data;

    /** @var Runtime */
    public $runtime;

    public function __construct(array $params, Runtime $runtime)
    {
        $this->runtime = $runtime;
        if (!isset($params['action'])) {
            throw new Exception('Missing action parameter', 400);
        }
        $this->params = $params;
        $method = $this->params['action'] . 'Action';
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid action ' . $this->params['action'], 400);
        }
        $this->{$method}($this->params);
    }

    public function checkLicenseAction(array $params)
    {
        if(!isset($params['license'])) {
            throw new InvalidArgumentException('Missing license parameter');
        }
        $post = $this->curl(VENDOR['apiLicense'], [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['license' => $params['license']]),
        ]);
        if (isset($post->json->error)) {
            throw new Exception($post->json->error->message, 403);
        }
        $this->response = 200 == $this->code ? 'Valid license key' : 'Unable to check license';
    }

    public function checkDatabaseAction(array $params)
    {
        try {
            $database = new Database(
                $params['host'],
                $params['port'],
                $params['name'],
                $params['user'],
                $params['userPassword']
            );
            $database->checkEmpty();
            $database->checkPrivileges();
            $this->code = 200;
            $this->response = sprintf('Database %s OK', $params['name']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 503);
        }
    }

    public function cPanelProcessAction(array $params)
    {
        try {
            $cpanel = new Cpanel($params['user'], $params['password']);
            $createDb = $cpanel->setupMysql();
            $this->code = 200;
            $this->response = 'cPanel process completed';
            $this->data['db'] = $createDb;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 503);
        }
    }

    public function cPanelHtaccessHandlersAction(array $params)
    {
        $filePath = $this->runtime->absPath . '.htaccess';
        if (!@is_readable($filePath)) {
            $this->code = 404;
            $this->response = 'No .htaccess found';

            return;
        }
        $handlers = Cpanel::getHtaccessHandlers($filePath);
        if (isset($handlers)) {
            $this->code = 200;
            $this->response = 'cPanel .htaccess handlers found';
            $this->data['handlers'] = trim($handlers);
        } else {
            $this->code = 404;
            $this->response = 'No cPanel .htaccess handlers found';
        }
    }

    public function downloadAction(array $params)
    {
        if(!isset($params['software'])) {
            throw new InvalidArgumentException('Missing software');
        }
        $fileBasename = 'chevereto-pkg-' . substr(bin2hex(random_bytes(8)), 0, 8) . '.zip';
        $filePath = $this->runtime->absPath . $fileBasename;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        $isPost = false;
        $zipball = APPLICATIONS[$params['software']]['zipball'] ?? null;
        if (!isset($zipball)) {
            throw new Exception('Invalid software parameter', 400);
        }
        if ($params['software'] == 'chevereto') {
            $isPost = true;
        } else {
            $params = null;
            $get = $this->curl($zipball);
            if(!isset($get->json->zipball_url)) {
                throw new RuntimeException('No zipball for ' . $params['software']);
            }
            $zipball = $get->json->zipball_url;
        }
        $curl = $this->downloadFile($zipball, $params, $filePath, $isPost);
        // Default chevereto.com API handling
        if (isset($curl->json->error)) {
            throw new RuntimeException($curl->json->error->message, $curl->json->status_code);
        }
        // Everybody else
        if (200 != $curl->transfer['http_code']) {
            throw new RuntimeException('[HTTP ' . $curl->transfer['http_code'] . '] ' . $zipball, $curl->transfer['http_code']);
        }
        $fileSize = filesize($filePath);
        $this->response = strtr('Downloaded %f (%w @%s)', array(
            '%f' => $fileBasename,
            '%w' => $this->getFormatBytes($fileSize),
            '%s' => $this->getBytesToMb($curl->transfer['speed_download']) . 'MB/s.',
        ));
        $this->data['fileBasename'] = $fileBasename;
        $this->data['filePath'] = $filePath;
    }

    public function extractAction(array $params)
    {
        if (!isset($params['software'])) {
            throw new Exception('Missing software parameter', 400);
        } elseif (!isset(APPLICATIONS[$params['software']])) {
            throw new Exception(sprintf('Unknown software %s', $params['software']), 400);
        }

        $software = APPLICATIONS[$params['software']];

        if (!isset($params['workingPath'])) {
            throw new Exception('Missing workingPath parameter', 400);
        }
        $workingPath = $params['workingPath'];
        if (!file_exists($workingPath) && !@mkdir($workingPath)) {
            throw new Exception(sprintf("Working path %s doesn't exists and can't be created", $workingPath), 503);
        }
        if (!is_readable($workingPath)) {
            throw new Exception(sprintf('Working path %s is not readable', $workingPath), 503);
        }

        $filePath = $params['filePath'];
        if (!is_readable($filePath)) {
            throw new Exception(sprintf("Can't read %s", basename($filePath)), 503);
        }
        $zipExt = new ZipArchiveExt();
        $timeStart = microtime(true);
        $zipOpen = $zipExt->open($filePath);
        if (true !== $zipOpen) {
            throw new Exception(strtr("Can't extract %f - %m (ZipArchive #%z)", array(
                '%f' => $filePath,
                '%m' => 'ZipArchive ' . $zipOpen . ' error',
                '%z' => $zipOpen,
            )), 503);
        }
        $numFiles = $zipExt->numFiles - 1; // because of top level folder
        $folder = $software['folder'];
        if ($params['software'] == 'chevereto-free') {
            $comment = $zipExt->getArchiveComment();
            $folder = str_replace('/', '-', $folder) . substr($comment, 0, 7);
        }
        $extraction = $zipExt->extractSubdirTo($workingPath, $folder);
        if (!empty($extraction)) {
            throw new Exception(implode(', ', $extraction));
        }
        $zipExt->close();
        $timeTaken = round(microtime(true) - $timeStart, 2);
        @unlink($filePath);

        $htaccessFiepath = $workingPath . '.htaccess';
        if (!empty($params['appendHtaccess']) && file_exists($htaccessFiepath)) {
            file_put_contents($htaccessFiepath, "\n\n" . $params['appendHtaccess'], FILE_APPEND | LOCK_EX);
        }
        $this->code = 200;
        $this->response = strtr('Extraction completeted (%n files in %ss)', ['%n' => $numFiles, '%s' => $timeTaken]);
    }

    public function createSettingsAction(array $params)
    {
        if(!isset($params['filePath'])) {
            throw new InvalidArgumentException('Missing filePath');
        }
        $settings = [];
        foreach ($params as $k => $v) {
            $settings["%$k%"] = $v;
        }
        $template = '<' . "?php
\$settings['db_host'] = '%host%';
\$settings['db_port'] = '%port%';
\$settings['db_name'] = '%name%';
\$settings['db_user'] = '%user%';
\$settings['db_pass'] = '%userPassword%';
\$settings['db_table_prefix'] = 'chv_';
\$settings['db_driver'] = 'mysql';
\$settings['db_pdo_attrs'] = [];
\$settings['debug_level'] = 1;";
        $php = strtr($template, $settings);
        put($params['filePath'], $php);
        $this->code = 200;
        $this->response = 'Settings file OK';
    }

    public function submitInstallFormAction(array $params)
    {
        $installUrl = $this->runtime->rootUrl;
        $missing = [];
        $required = ['username', 'email', 'password', 'email_from_email', 'email_incoming_email', 'website_mode'];
        if(PHP_SAPI === 'cli') {
            $required[] = 'website';
            $installUrl = rtrim($params['website'], '/') . '/';
        }
        foreach($required as $param) {
            if(!isset($params[$param])) {
                $missing[] = $param;
            }
        }
        if($missing !== []) {
            throw new InvalidArgumentException(sprintf('Missing %s', implode(', ', $missing)));
        }
        if(isDocker()) {
            $installUrl = 'http://localhost/';
        }
        $installUrl .= 'install';
        $post = $this->curl($installUrl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);
        if (!empty($post->json->error)) {
            throw new Exception($post->json->error->message, $post->json->error->code);
        }
        if (preg_match('/system error/i', $post->raw)) {
            throw new Exception('System error :(', 400);
        }
        if (preg_match('/<p class="highlight\s.*">(.*)<\/p>/', $post->raw, $post_errors)) {
            throw new Exception(strip_tags(str_replace('<br><br>', ' ', $post_errors[1])), 400);
        }
        $this->code = 200;
        $this->response = 'Setup complete';
    }

    /**
     * @param string $url      Target download URL
     * @param string $params   Request params
     * @param string $filePath Location to save the downloaded file
     * @param bool   $post     TRUE to download using a POST request
     * @param return curl handle
     */
    public function downloadFile(string $url, array $params = null, string $filePath, bool $post = true)
    {
        $fp = @fopen($filePath, 'wb+');
        if (!$fp) {
            throw new Exception("Can't open temp file " . $filePath . ' (wb+)');
        }
        $ops = [
            CURLOPT_FILE => $fp,
        ];
        if ($params) {
            $ops[CURLOPT_POSTFIELDS] = http_build_query($params);
        }
        if ($post) {
            $ops[CURLOPT_POST] = true;
        }
        $curl = $this->curl($url, $ops);
        fclose($fp);

        return $curl;
    }

    public function curl(string $url, array $curlOpts = []): object
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Chevereto Installer');
        if(PHP_SAPI === 'cli') {
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progressCallback');
            curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
        }
        $fp = false;
        foreach ($curlOpts as $k => $v) {
            if (CURLOPT_FILE == $k) {
                $fp = $v;
            }
            curl_setopt($ch, $k, $v);
        }
        logger("Fetching $url\n");
        $file_get_contents = @curl_exec($ch);
        logger("\n");
        $transfer = curl_getinfo($ch);
        if (curl_errno($ch)) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Curl error ' . $curl_error, 503);
        }
        curl_close($ch);
        $return = new stdClass();
        if (is_resource($fp)) {
            rewind($fp);
            $return->raw = stream_get_contents($fp);
        } else {
            $return->raw = $file_get_contents;
        }
        if (false !== strpos($transfer['content_type'], 'application/json')) {
            $return->json = json_decode($return->raw);
            if (is_resource($fp)) {
                $meta_data = stream_get_meta_data($fp);
                @unlink($meta_data['uri']);
            }
        }
        $this->code = $transfer['http_code'];
        if (200 != $this->code && !isset($return->json)) {
            $return->json = new stdClass();
            $return->json->error = new stdClass();
            $return->json->error->message = 'Error performing HTTP request';
            $return->json->error->code = $this->code;
        }
        $return->transfer = $transfer;

        return $return;
    }

    /**
     * @param string $bytes bytes to be formatted
     * @param int    $round how many decimals you want to get, default 1
     *
     * @return string formatted size string like 10 MB
     */
    public function getFormatBytes($bytes, $round = 1)
    {
        if (!is_numeric($bytes)) {
            return false;
        }
        if ($bytes < 1000) {
            return "$bytes B";
        }
        $units = array('KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        foreach ($units as $k => $v) {
            $multiplier = pow(1000, $k + 1);
            $threshold = $multiplier * 1000;
            if ($bytes < $threshold) {
                $size = round($bytes / $multiplier, $round);

                return "$size $v";
            }
        }
    }

    /**
     * Converts bytes to MB.
     *
     * @param string $bytes bytes to be formatted
     *
     * @return float MB representation
     */
    public function getBytesToMb($bytes, $round = 2)
    {
        $mb = $bytes / pow(10, 6);
        if ($round) {
            $mb = round($mb, $round);
        }

        return $mb;
    }
}
