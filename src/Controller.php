<?php

class Controller
{
    /** @var array */
    public $parameters;

    public function __construct(array $parameters)
    {
        if (!$parameters['action']) {
            throw new Exception('Missing `action` parameter', 400);
        }
        $this->parameters = $parameters;
        $method = $parameters['action'].'Action';
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid action `'.$parameters['action'].'`', 400);
        }
        $this->{$method}($this->parameters);
    }

    public function downloadAction(array $parameters)
    {
        // if (!$edition) {
        //     throw new Exception('Missing edition', 4000);
        // }
        // $zipball = $edition->zipball;
        // $file_basename = 'chevereto-pkg-'.randomString(8).'.zip';
        // $file_path = __ROOT_PATH__.$file_basename;
        // if (file_exists($file_path)) {
        //     @unlink($file_path);
        // }
        // $options = array(CURLOPT_USERAGENT => 'Chevereto web installer');
        // if ($_REQUEST['edition'] == 'paid') {
        //     $options[CURLOPT_POST] = 1;
        //     $options[CURLOPT_POSTFIELDS] = 'license='.$_REQUEST['license'];
        // }
        // $download = getUrlContent($zipball, $options);
        // $transfer = $download['transfer'];
        // if ($transfer['http_code'] !== 200) {
        //     $Output->setHttpStatus($transfer['http_code']);
        //     $json = json_decode($download['contents']);
        //     if (!$json) {
        //         throw new Exception($download['contents'], 4001);
        //     } else {
        //         $message = $_REQUEST['edition'] == 'free' ? $json->message : $json->error->message;
        //         throw new Exception($message, 4002);
        //     }
        // } else {
        //     if (!@rename($download['tmp_file_path'], $file_basename)) {
        //         throw new Exception("Can't save downloaded file ".$file_path, 5001);
        //     }
        //     @unlink($download['tmp_file_path']);
        //     $file_size = filesize($file_path);
        //     $Output->addData('download', array(
        //         'fileBasename' => $file_basename,
        //     ));
        //     $Output->setResponse(strtr('Downloaded %f (%w @%s)', array(
        //         '%f' => $file_basename,
        //         '%w' => formatBytes($file_size),
        //         '%s' => bytesToMb($transfer['speed_download']).'MB/s.',
        //     )));
        // }
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
}
