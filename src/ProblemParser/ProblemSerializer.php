<?php 

namespace TaskChecker\ProblemParser;

use TaskChecker\Problem;

class ProblemSerializer
{
    /**
     * Serializes problem list to a JSON string
     *
     * @return string 
     */
    public function serialize(array $problems)
    {
        $array = $this->serializeToArray($problems);
        return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    private function serializeToArray(array $problems)
    {
        $json = [];
        foreach ($problems as $problem) {
            $json[] = $this->serializeProblem($problem);
        }

        return $json;
    }

    private function serializeProblem(Problem $problem)
    {
        return [
            'id'            =>  $problem->getId(),
            'name'          =>  $problem->getName(),
            'description'   =>  $problem->getDescription(),
            'relativeUrl'   =>  $problem->getRelativeUrl(),
            'codeSample'    =>  $problem->getCodeSample(),
            'codeSampleUrl' =>  $problem->getCodeSampleUrl(),
            'hints'         =>  $problem->getHints(),
            'examples'      =>  $problem->getExamples()
        ];
    }
    
    /**
     * @return Problem[] 
     */
    public function deserialize($jsonString)
    {
        $array = json_decode($jsonString, true);
        if (null === $array) {
            throw new DeserializeException(json_last_error_msg());
        }

        $problems = [];
        foreach ($array as $item) {
            $problems[] = $this->deserializeProblem($item);
        }

        return $problems;
    }

    private function deserializeProblem(array $item)
    {
        $id = $this->readOne($item, 'id');
        $name = $this->readOne($item, 'name');
        $description = $this->readOne($item, 'description');

        $problem = new Problem($id, $name, $description);

        $problem->relativeUrl = $this->readOne($item, 'relativeUrl');
        $problem->codeSample = $this->readOne($item, 'codeSample');
        $problem->codeSampleUrl = $this->readOne($item, 'codeSampleUrl');
        $problem->hints = $this->readMany($item, 'hints');
        $problem->examples = $this->readMany($item, 'examples');

        return $problem;
    }

    private function readOne(array $data, $key)
    {
        assert(array_key_exists($key, $data));
        $value = $data[$key];
        assert(is_scalar($value) || is_null($value));
        return $value;
    }
    
    private function readMany(array $data, $key)
    {
        assert(array_key_exists($key, $data));
        $value = $data[$key];
        assert(is_array($value));
        foreach ($value as $item) {
            assert(is_string($item));
        }
        return $value;
    }
}