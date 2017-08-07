<?php 

use TaskChecker\ProblemParser\Parser;

require_once __DIR__ . '/../src/bootstrap.php';
$path = $argv[1];
$html = file_get_contents($path);

$parser = new Parser;
$problems = $parser->parsePage($html, 'http://example.com/1?2');

print_r($problems);

