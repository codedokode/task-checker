<?php 

require_once __DIR__ . '/../src/bootstrap.php';

$args = array_slice($argv, 1);
list($baseDir, $baseUrl) = $args;

assert(!!$baseDir);
assert(!!$baseUrl);

chdir($baseDir);
$childArgs = [];

$dirIter = new RecursiveDirectoryIterator(
    '.', 
    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
);

$fileIter = new RecursiveIteratorIterator($dirIter);

foreach ($fileIter as $file) {
    if (!preg_match("/\.html?$/", $file->getBaseName())) {
        continue;
    }

    $localPath = preg_replace("~^\./~", '/', $file->getPathname());
    $localPath = ltrim($localPath, '/');
    $baseProto = parse_url($baseUrl, PHP_URL_SCHEME); 
    $baseHost = parse_url($baseUrl, PHP_URL_HOST); 

    assert(!!$baseProto);
    assert(!!$baseHost);

    $pageUrl = "{$baseProto}://{$baseHost}/{$localPath}";
    $childArgs[] = "{$file->getPathname()}:$pageUrl";
}

assert(count($childArgs) > 0);
$escapedArgs = array_map('escapeshellarg', $childArgs);
$scriptPath = __DIR__ . '/parse-problems.php';
$command = "php \"$scriptPath\" " . implode(' ', $escapedArgs);

$exitCode = 0;
passthru($command, $exitCode);
exit($exitCode);

