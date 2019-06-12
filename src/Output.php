<?php
class Output
{
    public function __construct()
    {
        $this->status = null;
        $this->response = null;
        $this->request = $_REQUEST;
    }

    public function setHttpStatus($code)
    {
        $this->status = array(
            'code' => $code,
            'description' => getHttpStatusDesc($code),
        );
    }

    public function setResponse($message, $code = null)
    {
        $this->response = array(
            'code' => $code,
            'message' => $message,
        );
    }

    public function addData($prop, $var = null)
    {
        if (!isset($this->data)) {
            $this->data = new stdClass();
        }
        if (is_array($var)) {
            // $var = json_encode($var, JSON_FORCE_OBJECT);
        }
        $this->data->{$prop} = $var;
    }

    public function exec()
    {
        error_reporting(0);
        @ini_set('display_errors', false);
        if (ob_get_level() === 0 and !ob_start('ob_gzhandler')) {
            ob_start();
        }
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').'GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: application/json; charset=UTF-8');
        if (!isset($this->data) && !isset($this->response)) {
            $this->setHttpStatus(400);
        } else {
            if (!isset($this->status['code'])) {
                $this->setHttpStatus(200);
            }
        }
        if (!isset($this->response)) {
            $this->setResponse($this->status['description'], $this->status['code']);
        } else {
            if (!isset($this->response['code'])) {
                $this->response['code'] = $this->status['code'];
            }
        }
        $json = json_encode($this, JSON_FORCE_OBJECT);
        if (!$json) {
            $this->setHttpStatus(500);
            $this->setResponse("Data couldn't be encoded", 500);
            $this->data = null;
        }
        if (is_int($this->status['code'])) {
            setHttpStatusCode($this->status['code']);
        }
        echo $this->data ? $json : json_encode($this, JSON_FORCE_OBJECT);
        die();
    }
}