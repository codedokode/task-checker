<?php 

namespace TaskChecker\ProblemParser;

use Masterminds\HTML5;
use Mf2;
use TaskChecker\Problem;

/**
 * Parsers source code for h-php-problem microformat 
 * and returns an array of Problem objects.
 *
 * Microformat is based on microformats2 spec: 
 * http://microformats.org/wiki/microformats-2 
 * 
 * For description see documentation and tests.
 */
class Parser
{
    const ROOT_CLASS = 'h-php-problem';

    public function parsePage($html, $url)
    {
        $htmlParser = new HTML5;
        $dom = $htmlParser->loadHTML($html);
        // $erros = $htmlParser->getErrors();
        
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[contains(@class, "h-php-problem")]');

        $problems = [];

        foreach ($nodes as $node) {
            $classes = $node->getAttribute('class');
            // Check that the class name is not a part of a word
            if (!preg_match("/(^|\s)h-php-problem(\s|$)/", $classes)) {
                continue;
            }

            $id = $node->getAttribute('id');

            if (!$id) {
                throw new ParseException("A problem block must have an id");
            }

            if (!preg_match("/^problem-(.*)$/", $id, $m)) {
                throw new ParseException("An id must start with problem-, id='$id' given");
            }

            $problemId = $m[1];
            $problemHash = $id;
            $problemHtml = $htmlParser->saveHtml($node);

            $parser = new Mf2\Parser($problemHtml, $url);
            $output = $parser->parse();

            $items = $output['items'];
            assert(count($items) == 1);

            $item = $items[0];

            $types = $item['type'];
            if (!in_array(self::ROOT_CLASS, $types)) {
                continue;
            }

            $problem = $this->parseProblemMarkup(
                $item['properties'], 
                $problemId, 
                $problemHash
            );

            $problem->relativeUrl = $this->makeProblemUrl($url, $problemHash);

            $problems[] = $problem;            
        }

        return $problems;
    }

    /**
     * @return Problem 
     */
    private function parseProblemMarkup(array $properties, $problemId, $problemHash)
    {
        $required = ['name', 'description'];
        foreach ($required as $propertyName) {
            if (!array_key_exists($propertyName, $properties)) {
                throw new ParseException(
                    "Problem #{$problemHash} has no '$propertyName' property");
            }
        }

        $allowed = array_merge($required, [
            'code-sample', 
            'code-sample-url', 
            'hint',
            'example'
        ]);

        // Mf2 Parser adds some extra properties
        unset($properties['url']);
        unset($properties['photo']);

        foreach ($properties as $key => $value) {
            if (!in_array($key, $allowed)) {
                throw new ParseException(
                    "Invalid property '$key' found in problem #$problemHash");
            }
        }

        if (count($properties['name']) > 1) {
            throw new ParseException("There should be only one name tag in #$problemHash");
        }

        $name = $properties['name'][0];

        $descriptionParts = $this->readManyProps($properties, 'description', true);
        $description = implode("\n", $descriptionParts);
        $problem = new Problem($problemId, $name, $description);

        $problem->codeSampleUrl = $this->readOnePropOrNull(
            $properties, 'code-sample-url', false, $problemHash);

        $problem->codeSample = $this->readOnePropOrNull(
            $properties, 'code-sample', false, $problemHash);

        $problem->hints = $this->readManyProps($properties, 'hint', true);
        $problem->examples = $this->readManyProps($properties, 'example', true);

        return $problem;
    }

    private function readManyProps(array $properties, $name, $isHtml)
    {
        if (!array_key_exists($name, $properties)) {
            return [];
        }

        if ($isHtml) {
            $result = array_column($properties[$name], 'html');
        } else {
            $result = $properties[$name];
        }

        return $result;
    }

    private function readOnePropOrNull(array $properties, $name, $isHtml, $problemHash)
    {
        if (!array_key_exists($name, $properties)) {
            return null;
        }

        if (count($properties[$name]) > 1) {
            throw new ParseException(
                "There can be only one property '$name' in problem #$problemHash, actually got " . 
                count($properties[$name]));
        }

        if ($isHtml) {
            $result = $properties[$name][0]['html'];
        } else {
            $result = $properties[$name][0];
        }

        return $result;
    }

    private function makeProblemUrl($url, $hash)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null) {
            $path = '/';
        }

        $query = parse_url($url, PHP_URL_QUERY);

        $relativeUrl = $path . ($query !== null ? '?' : '') . $query . 
            '#' . $hash;

        return $relativeUrl;
    }
}

