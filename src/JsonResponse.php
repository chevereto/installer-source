<?php

class JsonResponse
{
    /** var array [code => , description =>,]*/
    public $status;

    /** var array [code => , message =>,]*/
    public $response;

    public function __construct(string $message, $code = null)
    {
        $this->setResponse($message, $code);
    }

    public function setResponse(string $message, $code = null)
    {
        $this->response = array(
            'code' => $code,
            'message' => $message,
        );
    }

    public function setStatus($code)
    {
        $this->status = array(
            'code' => $code,
            'description' => $this->getHttpStatusDesc($code),
        );
    }

    public function getHttpStatusDesc($code)
    {
        $codes_to_desc = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Reserved',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            510 => 'Not Extended',
        );
        if (array_key_exists($code, $codes_to_desc)) {
            return $codes_to_desc[$code];
        }
    }

    public function setStatusCode($code)
    {
        http_response_code($code);
    }

    public function addData($key, $var = null)
    {
        if (!isset($this->data)) {
            $this->data = new stdClass();
        }
        $this->data->{$key} = $var;
    }

    public function send()
    {
        @ini_set('display_errors', false);
        if (ob_get_level() === 0 and !ob_start('ob_gzhandler')) {
            ob_start();
        }
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').'GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: application/json; charset=UTF-8');
        if (!isset($this->data) && !isset($this->response)) {
            $this->setStatus(400);
        } else {
            if (!isset($this->status['code'])) {
                $this->setStatus(200);
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
            $this->setStatus(500);
            $this->setResponse("Data couldn't be encoded", 500);
            $this->data = null;
        }
        if (is_int($this->status['code'])) {
            $this->setStatusCode($this->status['code']);
        }
        echo $this->data ? $json : json_encode($this, JSON_FORCE_OBJECT);
        die();
    }
}
