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
    if (!file_exists($filename)) {
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            createPath($dirname);
        }
    }
    if (false === @file_put_contents($filename, $contents, FILE_APPEND)) {
        throw new RuntimeException('Unable to write content to file ' . $filename);
    }
}
function createPath(string $path): string
{
    if (!mkdir($path, 0777, true)) {
        throw new RuntimeException('Unable to create path ' . $path);
    }
    return $path;
}
