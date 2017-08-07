<?php 

use TaskChecker\ProblemParser\Parser;
use TaskChecker\ProblemParser\ProblemSerializer;

require_once __DIR__ . '/../src/bootstrap.php';

function printUsage($defaultSavePath)
{
    fwrite(
        STDERR, 
        "Usage: php script.php [--save-path=/path/to/save.json] path1:URL1 [path2:URL2 ...]\n\n" . 
        "Parses HTML files at given paths, extracts problem descriptions and saves them to  
        a JSON file (default location is $defaultSavePath)\n" . 
        "See documentation for details\n"
    );
}

$prefix = 'ProblemParser:';
$problemService = $app['problem_service'];
$defaultSavePath = $problemService->getProblemListLocation();
$savePath = $defaultSavePath;
$useOtherPath = false;
$filesAndUrls = [];

foreach (array_slice($argv, 1) as $arg) {

    if (in_array($arg, ['-h', '--help'])) {
        printUsage($defaultSavePath);
        exit(0);
    }

    if (preg_match("~^--save-path=(.+)$~", $arg, $m)) {
        if ($useOtherPath) {
            throw new \Exception("$prefix --save-path can be specified only once");
        }

        $useOtherPath = true;
        $savePath = $m[1];
        continue;
    }

    $parts = explode(':', $arg, 2);
    if (count($parts) == 2) {
        list($file, $url) = $parts;
        $filesAndUrls[$file] = $url;
        continue;
    }

    throw new \Exception("$prefix Invalid argument: $arg");
}

if (!$filesAndUrls) {
    fwrite(STDERR, "$prefix No file names given\n\n");
    printUsage($defaultSavePath);
    exit(1);
}

$problemParser = $app['problem_parser'];
$problems = [];

foreach ($filesAndUrls as $path => $url) {
    if (!is_file($path)) {
        throw new \Exception("$prefix File doesn't exist or is not a file: $path");
    }

    $html = file_get_contents($path);
    $newProblems = $problemParser->parsePage($html, $url);
    $problems = array_merge($problems, $newProblems);
}

$problemIds = array_map(function ($p) {return $p->getId();}, $problems);
$problemCount = array_count_values($problemIds);
foreach ($problemCount as $id => $count) {
    if ($count > 1) {
        throw new \Exception("$prefix Problem with id $id is repeated twice");
    }
}

$serializer = new ProblemSerializer;
$string = $serializer->serialize($problems);
file_put_contents($savePath, $string);


