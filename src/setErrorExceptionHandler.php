<?php

const ERROR_TABLE = [
    E_ERROR => 'Fatal error',
    E_WARNING => 'Warning',
    E_PARSE => 'Parse error',
    E_NOTICE => 'Notice',
    E_CORE_ERROR => 'Core error',
    E_CORE_WARNING => 'Core warning',
    E_COMPILE_ERROR => 'Compile error',
    E_COMPILE_WARNING => 'Compile warning',
    E_USER_ERROR => 'Fatal error',
    E_USER_WARNING => 'Warning',
    E_USER_NOTICE => 'Notice',
    E_STRICT => 'Strict standars',
    E_RECOVERABLE_ERROR => 'Recoverable error',
    E_DEPRECATED => 'Deprecated',
    E_USER_DEPRECATED => 'Deprecated',
];

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
}, $phpSettings['error_reporting']);

set_exception_handler(function (Throwable $e) {
    $trace = $e->getTrace();
    $traceTemplate = "#%k% %file%:%line%\n%class%%type%%function%%args%";
    $argsTemplate = "Arg#%k%\n%arg%";

    switch (true) {
        case $e instanceof ErrorException:
            $type = ERROR_TABLE[$e->getSeverity()];
            $thrown = $e->getMessage();

            array_shift($trace);
            array_shift($trace);
            break;
        case $e instanceof Error:
            $type = 'PHP';
            $thrown = $e->getMessage();
            break;
        default:
            $type = 'Exception';
            $thrown = $type . ' thrown';
            break;
    }
    $message = 'in ' . $e->getFile() . ':' . $e->getLine();
    $retrace = [];
    foreach ($trace as $k => $v) {
        $args = [];
        foreach ($v['args'] as $ak => $av) {
            $arg = var_export($av, true);
            $args[] = strtr($argsTemplate, [
                '%k%' => $ak,
                '%arg%' => $arg,
            ]);
        }
        $retrace[] = strtr($traceTemplate, [
            '%k%' => $k,
            '%file%' => $v['file'] ?? '',
            '%line%' => $v['line'] ?? '',
            '%class%' => $v['class'] ?? '',
            '%type%' => $v['type'] ?? '',
            '%function%' => $v['function'] ?? '',
            '%args%' => empty($args) ? '' : ("\n--\n" . implode("\n--\n", $args)),
        ]);
    }
    $cols = 80;
    $hypens = str_repeat('-', $cols);
    $halfHypens = substr($hypens, 0, $cols / 2);
    $stack = implode("\n$halfHypens\n", $retrace);
    $tags = [
        '%type%' => $type,
        '%datetime%' => date('Y-m-d H:i:s'),
        '%thrown%' => $thrown,
        '%message%' => $message,
        '%stack%' => $stack,
        '%trace%' => empty($retrace) ? '' : "Trace:\n"
    ];
    $screenTpl = '<h1>[%type%] %thrown%</h1><p>%message%</p>' . "\n\n" . "%trace%<pre><code>%stack%</code></pre>";
    $textTpl = "%datetime% [%type%] %thrown%: %message%\n\n%trace%%stack%";

    $text = "$hypens\n" . strtr($textTpl, $tags) . "\n$hypens\n\n";

    append(ERROR_LOG_FILEPATH, $text);

    echo strtr($screenTpl, $tags);
    die();
});
