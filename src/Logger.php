<?php

class Logger
{
    /** @var string */
    public $name;
    /** @var array */
    public $log;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->log = array();
    }

    public function addMessage(string $message)
    {
        $this->log[] = $message;
    }
}
