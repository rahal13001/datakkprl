<?php

$outputPrefix = $argv[count($argv) - 1] ?? null;

if (! is_string($outputPrefix) || $outputPrefix === '') {
    exit(2);
}

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

if (! is_string($png) || file_put_contents($outputPrefix.'-1.png', $png) === false) {
    exit(3);
}

exit(0);
