<?php

function dump()
{
    echo '<pre>';
    foreach (func_get_args() as $value) {
        print_r($value);
    }
    echo '</pre>';
}
