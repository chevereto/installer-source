<?php

function password(int $length)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';

    return substr(str_shuffle($chars), 0, $length);
}
function dump()
{
    echo '<pre>';
    foreach (func_get_args() as $value) {
        print_r($value);
    }
    echo '</pre>';
}
function append(string $filename, string $contents)
{
    prepareDirFor($filename);
    if (false === @file_put_contents($filename, $contents, FILE_APPEND)) {
        throw new RuntimeException('Unable to append content to file ' . $filename);
    }
}
function put(string $filename, string $contents)
{
    prepareDirFor($filename);
    if (false === @file_put_contents($filename, $contents)) {
        throw new RuntimeException('Unable to put content to file ' . $filename);
    }
}

function prepareDirFor(string $filename)
{
    if (!file_exists($filename)) {
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            createPath($dirname);
        }
    }
}

function createPath(string $path): string
{
    if (!mkdir($path, 0777, true)) {
        throw new RuntimeException('Unable to create path ' . $path);
    }
    return $path;
}

function logger(string $message)
{
    if(PHP_SAPI !== 'cli') {
        return;
    }
    fwrite(fopen('php://stdout', 'r+'), $message);
}

function progressCallback($resource, $download_size = 0, $downloaded = 0, $upload_size = 0, $uploaded = 0)
{
    if($download_size == 0) {
        return;
    }
    logger(progress_bar($downloaded, $download_size, ' download'));
}

function progress_bar($done, $total, $info="", $width=50) {
    $perc = round(($done * 100) / $total);
    $bar = round(($width * $perc) / 100);
    return sprintf("  %s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
}