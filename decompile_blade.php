<?php
$compiled = file_get_contents('storage/framework/views/26a9ab2ba691226a1a7cc4901047e1f9.php');

// Remove the compiled component header
$compiled = preg_replace('/<\?php\s+extract\(.*\);\s+.*?\?>/s', '', $compiled);
$compiled = preg_replace('/<\?php\s+\$__env->startComponent\(.*?\);\s+\?>/s', '', $compiled);
$compiled = preg_replace('/<\?php\s+echo\s+\$__env->renderComponent\(\);\s+\?>/s', '', $compiled);
$compiled = preg_replace('/<\?php\s+endif;\s+\?>/s', '@endif', $compiled);
$compiled = preg_replace('/<\?php\s+if\((.*?)\):\s+\?>/s', '@if($1)', $compiled);
$compiled = preg_replace('/<\?php\s+elseif\((.*?)\):\s+\?>/s', '@elseif($1)', $compiled);
$compiled = preg_replace('/<\?php\s+else:\s+\?>/s', '@else', $compiled);
$compiled = preg_replace('/<\?php\s+foreach\((.*?)\):\s+\?>/s', '@foreach($1)', $compiled);
$compiled = preg_replace('/<\?php\s+endforeach;\s+\$__env->popLoop\(\);\s+\$loop\s+=\s+\$__env->getLastLoop\(\);\s+\?>/s', '@endforeach', $compiled);
$compiled = preg_replace('/<\?php\s+echo\s+e\((.*?)\);\s+\?>/s', '{{ $1 }}', $compiled);
$compiled = preg_replace('/<\?php\s+echo\s+(.*?);\s+\?>/s', '{!! $1 !!}', $compiled);
// Remove the error handling wrappers
$compiled = preg_replace('/<\?php\s+\$__errorArgs\s+=\s+\[\'(.*?)\'\];.*?if\s*\(\$__messageOriginal\):\s+\?>/s', '@error(\'$1\')', $compiled);
$compiled = preg_replace('/<\?php\s+unset\(\$__errorArgs,\s+\$__bag\);\s+\?>/s', '', $compiled);

file_put_contents('recovered.blade.php', trim($compiled));
echo "Recovered to recovered.blade.php\n";
