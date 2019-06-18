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
        if (!$params['action']) {
            throw new Exception('Missing `action` parameter', 400);
        }
        $this->params = $params;
        $method = $params['action'].'Action';
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid action `'.$params['action'].'`', 400);
        }
        $action = $this->{$method}($this->params);
    }

    public function licenseCheckAction(array $params)
    {
        $post = $this->curl('https://chevereto.com/api/license/check', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['license' => $params['license']]),
        ]);
        if ($post->json->error) {
            throw new Exception($post->json->error->message, 403);
        }
        $this->response = 200 == $this->code ? 'Valid license key' : 'Unable to check license';
    }

    public function dabataseCheckAction(array $params)
    {
        try {
            $database = new Database(
                $params['host'], $params['port'], $params['name'], $params['user'], $params['userPassword']
            );
            $database->checkEmpty();
            $database->checkPrivileges();
            $this->code = 200;
            $this->response = 'Database OK';
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 503);
        }
    }

    public function downloadAction(array $params)
    {
        $fileBasename = 'chevereto-pkg-'.substr(bin2hex(random_bytes(8)), 0, 8).'.zip';
        $filePath = $this->runtime->absPath.$fileBasename;
        if (file_exists($downloadPath)) {
            @unlink($downloadPath);
        }
        $isPost = false;
        $zipball = APPLICATIONS[$params['software']]['zipball'];
        if (!$zipball) {
            throw new Exception('Invalid software parameter', 400);
        }
        if ($params['software'] == 'chevereto') {
            $isPost = true;
        } else {
            $params = null;
        }
        $curl = $this->downloadFile($zipball, $params, $filePath, $isPost);
        // Default chevereto.com API handling
        if ($curl->json->error) {
            throw new Exception($curl->json->error->message, $curl->json->status_code);
        }
        // Everybody else
        if (200 != $curl->transfer['http_code']) {
            throw new Exception('[HTTP '.$curl->transfer['http_code'].'] '.$zipball, $curl->transfer['http_code']);
        }
        $fileSize = filesize($filePath);
        $this->response = strtr('Downloaded `%f` (%w @%s)', array(
            '%f' => $fileBasename,
            '%w' => $this->getFormatBytes($fileSize),
            '%s' => $this->getBytesToMb($curl->transfer['speed_download']).'MB/s.',
        ));
        $this->data['fileBasename'] = $fileBasename;
        $this->data['filePath'] = $filePath;
    }

    public function extractAction(array $params)
    {
        $this->code = 400;
        $this->response = 'Pal paico';
        // $file_path = __ROOT_PATH__.$_REQUEST['fileBasename'];
        // if (!is_readable($file_path)) {
        //     throw new Exception(sprintf("Can't read %s", $_REQUEST['fileBasename']), 5002);
        // }
        // // Unzip .zip
        // $ZipArchive = new ZipArchiveExt();
        // $time_start = microtime(true);
        // $open = $ZipArchive->open($file_path);
        // if ($open === true) {
        //     $num_files = $ZipArchive->numFiles - 1; // because of tl folder
        //     $folder = $edition->folder;
        //     if ($_REQUEST['edition'] == 'free') {
        //         $comment = $ZipArchive->getArchiveComment();
        //         $folder .= substr($comment, 0, 7);
        //     }
        //     $ZipArchive->extractSubdirTo(__ROOT_PATH__, $folder);
        //     $ZipArchive->close();
        //     $time_taken = round(microtime(true) - $time_start, 2);
        //     @unlink($file_path);
        //     // Also remove some free edition docs
        //     if ($_REQUEST['edition'] == 'paid') {
        //         foreach (array('AGPLv3', 'LICENSE', 'README.md') as $v) {
        //             @unlink(__ROOT_PATH__.$v);
        //         }
        //     }
        // } else {
        //     throw new Exception(strtr("Can't extract %f - %m", array(
        //                     '%f' => $file_path,
        //                     '%m' => 'ZipArchive '.$open.' error',
        //                 )), 5003);
        // }
        // $Output->setResponse(strtr('Extraction completeted (%n files in %ss)', array(
        //                 '%n' => $num_files,
        //                 '%s' => $time_taken,
        //             )));
        // // My job here is done. My planet needs me.
        // if (__INSTALLER_FILE__ != 'index.php') {
        //     @unlink(__INSTALLER_FILEPATH__);
        // }
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
            throw new Exception("Can't open temp file `".$filePath.'` (wb+)');
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

    /**
     * @return array [transfer =>, tmp_file_path =>, raw =>, json =>,]
     */
    public function curl(string $url, array $curlOpts = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Chevereto Installer');
        foreach ($curlOpts as $k => $v) {
            if (CURLOPT_FILE == $k) {
                $fp = $v;
            }
            curl_setopt($ch, $k, $v);
        }
        $file_get_contents = @curl_exec($ch);
        $transfer = curl_getinfo($ch);
        if (curl_errno($ch)) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Curl error '.$curl_error, 503);
        }
        curl_close($ch);
        $return = new stdClass();
        if (is_resource($fp)) {
            rewind($fp);
            $return->raw = stream_get_contents($fp);
        // $return->raw = $this->getBytesToMb($transfer['size_download']) < 0.5 ? $return->raw : '<data too big>';
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
