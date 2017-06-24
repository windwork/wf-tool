<?php
$pharDist = "bin/wf-tool.phar";
//$pharDist = "../../phar/wf-tool.phar";

$phar = new Phar($pharDist);
$phar->buildFromDirectory(__DIR__ . '/src');
$phar->compressFiles(Phar::GZ);
$phar->setStub("#!/usr/bin/env php\n<?php\nPhar::mapPhar('wf-tool.phar');\nrequire_once 'phar://wf-tool.phar/index.php';\n__HALT_COMPILER();\n?>");

print 'done';
