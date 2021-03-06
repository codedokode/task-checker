<?php

namespace TaskChecker\Module;

class Util extends BaseModule
{
    /**
     * Input: "
     *     a,b,c
     *     0,1,2
     *     4,5,6
     * "
     *
     * Output: [
     *     ['a' => 0, 'b' => 1, 'c' => 2],
     *     ['a' => 4, 'b' => 5, 'c' => 6]
     * ]
     */
    public function fromCsv($csvString)
    {
        $lines = explode("\n", $csvString);
        $lines = array_map("trim", $lines);
        $lines = array_filter($lines, function ($line) { return $line !== ''; });

        if (count($lines) < 2) {
            throw new \InvalidArgumentException(
                "Input data must contain at least 2 lines");
        }

        $data = array_map('str_getcsv', $lines);
        foreach ($data as $key => $row) {
            $data[$key] = array_map('trim', $row);
        }

        $headers = array_shift($data);

        $result = [];
        foreach ($data as $i => $row) {
            if (count($row) != count($headers)) {
                throw new \InvalidArgumentException(sprintf(
                    "Invalid value count at row %d, %d headers and %d cells",
                    $i + 1,
                    count($headers),
                    count($row)
                ));
            }

            foreach ($row as $key => $value) {
                if (is_numeric($value)) {
                    $row[$key] = floatval($value);
                }
            }

            $result[] = array_combine($headers, $row);
        }

        return $result;
    }
}