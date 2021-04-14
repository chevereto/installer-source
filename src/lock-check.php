<?php

$lockRelative = 'app/installer.lock';
if(file_exists($runtime->absPath . $lockRelative)) {
    if(PHP_SAPI === 'cli') {
        logger("Locked ($lockRelative)\n");
    } else {
        set_status_header(403);
    }
    die(255);
}