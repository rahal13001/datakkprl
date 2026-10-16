<?php

// Development-only, read-only package check. Schema files are public ECMA XSDs.
$schemas = realpath($argv[1] ?? '');
$documents = realpath($argv[2] ?? '');
if ($schemas === false || $documents === false || ! is_file($schemas.'/wml.xsd') || ! is_file($schemas.'/xml.xsd')) {
    fwrite(STDERR, "Usage: php tools/kkprl-document-schema-smoke.php <ECMA-transitional-XSD-directory> <DOCX-directory>\n");
    exit(2);
}
libxml_use_internal_errors(true);
$schemaRoot = htmlspecialchars('file:///'.ltrim(str_replace('\\', '/', $schemas), '/').'/', ENT_XML1 | ENT_QUOTES, 'UTF-8');
// ECMA leaves the XML namespace import without schemaLocation. Load the W3C
// declarations first, without changing any ECMA validation constraints.
$schema = '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">'
    .'<xs:import namespace="http://www.w3.org/XML/1998/namespace" schemaLocation="'.$schemaRoot.'xml.xsd"/>'
    .'<xs:import namespace="http://schemas.openxmlformats.org/wordprocessingml/2006/main" schemaLocation="'.$schemaRoot.'wml.xsd"/>'
    .'</xs:schema>';
$failures = 0;
$files = glob($documents.'/*.docx') ?: [];
if ($files === []) {
    throw new RuntimeException('No DOCX files to validate.');
}
foreach ($files as $file) {
    $zip = new ZipArchive;
    if ($zip->open($file) !== true) {
        throw new RuntimeException('Cannot open DOCX package.');
    }
    foreach (['word/document.xml', 'word/styles.xml', 'word/settings.xml', 'word/footer1.xml'] as $part) {
        libxml_clear_errors();
        $document = new DOMDocument;
        $xml = $zip->getFromName($part);
        $valid = is_string($xml) && $document->loadXML($xml, LIBXML_NONET) && $document->schemaValidateSource($schema);
        echo basename($file).' '.$part.': '.($valid ? 'PASS' : 'FAIL')."\n";
        if (! $valid) {
            $failures++;
            foreach (libxml_get_errors() as $error) {
                // XML validation diagnostics contain element/schema names, no document text.
                echo '  code '.$error->code.' line '.$error->line.': '.$error->message;
            }
        }
    }
    $zip->close();
}
exit($failures === 0 ? 0 : 1);
