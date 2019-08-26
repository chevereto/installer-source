<?php

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e) {
    $trace = $e->getTrace();
    $traceTemplate = "#%k% %file%:%line%\n%class%%type%%function%\n--\n%args%";
    $argsTemplate = "Arg#%k%\n%arg%";

    if ($e instanceof ErrorException) {
        $thrown = ERROR_TABLE[$e->getSeverity()];
        array_shift($trace);
        array_shift($trace);
    } else {
        $thrown = 'Exception thrown';
    }

    foreach ($trace as $k => $v) {
        $args = [];
        foreach ($v['args'] as $ak => $av) {
            $arg = var_export($av, true);
            $args[] = strtr($argsTemplate, [
                '%k%' => $ak,
                '%arg%' => $arg,
            ]);
        }
        $trace2[] = strtr($traceTemplate, [
            '%k%' => $k,
            '%file%' => $v['file'] ?? '',
            '%line%' => $v['line'] ?? '',
            '%class%' => $v['class'] ?? '',
            '%type%' => $v['type'] ?? '',
            '%function%' => $v['function'] ?? '',
            '%args%' => implode("\n--\n", $args),
        ]);
    }
    echo '<h1>[Error] ' . $thrown . '</h1><p>' . $e->getMessage() . '</p>' . "\n\n" . "Trace:\n<pre><code>" . implode("\n--------------------------------\n", $trace2) . '</code></pre>';
    die();
});
