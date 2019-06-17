<?php

class Controller
{
    /** @var array */
    public $parameters;

    /** @var string */
    public $response;

    /** @var array */
    public $data;

    /** @var Runtime */
    public $runtime;

    public function __construct(array $parameters, Runtime $runtime)
    {
        $this->runtime = $runtime;
        if (!$parameters['action']) {
            throw new Exception('Missing `action` parameter', 400);
        }
        $this->parameters = $parameters;
        $method = $parameters['action'].'Action';
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid action `'.$parameters['action'].'`', 400);
        }
        $action = $this->{$method}($this->parameters);
    }

    public function licenseCheckAction(array $parameters)
    {
        $post = $this->curl('https://chevereto.com/api/license/check', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['license' => $parameters['license']]),
        ]);
        if ($post->json->error) {
            throw new Exception($post->json->error->message, 403);
        }
        $this->response = 200 == $this->code ? 'Valid license key' : 'Unable to check license';
    }

    public function downloadAction(array $parameters)
    {
        $fileBasename = 'chevereto-pkg-'.substr(bin2hex(random_bytes(8)), 0, 8).'.zip';
        $filePath = $this->runtime->absPath.$fileBasename;
        if (file_exists($downloadPath)) {
            @unlink($downloadPath);
        }
        $post = $this->downloadFile('https://chevereto.com/api/download/latest', $parameters, $filePath);
        if ($post->json->error) {
            throw new Exception($post->json->error->message, $post->json->status_code);
        }
        $fileSize = filesize($filePath);
        $this->response = strtr('Downloaded `%f` (%w @%s)', array(
            '%f' => $fileBasename,
            '%w' => $this->getFormatBytes($fileSize),
            '%s' => $this->getBytesToMb($post->transfer['speed_download']).'MB/s.',
        ));
        $this->data['filepath'] = $filePath;
    }

    public function extractAction(array $parameters)
    {
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

    public function downloadFile(string $url, array $parameters = [], string $filePath)
    {
        $fp = @fopen($filePath, 'wb+');
        if (!$fp) {
            throw new Exception("Can't open temp file `".$filePath.'` (wb+)');
        }
        $post = $this->curl($url, [
            CURLOPT_POST => true,
            CURLOPT_FILE => $fp,
            CURLOPT_POSTFIELDS => http_build_query($parameters),
        ]);
        fclose($fp);

        return $post;
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
