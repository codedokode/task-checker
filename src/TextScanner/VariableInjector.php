<?php

namespace TextScanner;

class VariableInjector
{
    public function inject(TokenArray $program, array $variables, &$errors = array())
    {
        $errors = [];
        $replacements = [];

        foreach ($variables as $variable => $value) {

            try {                
                $range = $this->findVariableDefinition($program, $variable);
            } catch (VariableInjectException $e) {
                $errors[] = $e;
                continue;
            }

            $phpValue = $this->stringifyValue($value);
            $replacements[] = [$range, $phpValue];
        }

        if ($errors) {
            return null;
        }

        // create output text
        return $program->generateOutput($replacements);
    }
    
    /**
     * Find code like $var = EXPR;
     */
    private function findVariableDefinition(TokenArray $program, $varName)
    {
        $scanner = $program->scan();
        $varString = '$' . $varName;

        while (!$scanner->isEnd()) {

            $found = $scanner->goWhere(function ($scanner) use ($varString) {
                return $scanner->getTokenId() == T_VARIABLE && 
                        $scanner->getTokenString() == $varString;
            });

            if (!$found) {
                break;    
            }

            $scanner->next();

            if (!$scanner->readTokenId('=')) {
                continue;
            }

            $range = $scanner->matchValue();
            if (!$range) {
                continue;
            }

            if ($scanner->getTokenString() != ';') {
                throw new VariableInjectException(
                    $varName, 
                    VariableInjectException::ERROR_MISSING_SEMICOLON
                );
            }

            // Check range is OK
            // $pos = $range->getLineAndColumn();
            // if ($pos['line'] != $pos['endLine']) {
            //     throw new VariableInjectException($varName, ...);
            // }

            return $range;
        }

        throw new VariableInjectException($varName, 
            VariableInjectException::ERROR_DECL_NOT_FOUND);
    }

    private function stringifyValue($value)
    {
        $this->checkCanBeStringified($value);
        return var_export($value, true);
    }

    private function checkCanBeStringified($value)
    {
        if (is_bool($value) || is_string($value) || is_float($value) || is_int($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $element) {
                $this->checkCanBeStringified($element);
            }

            return;
        }

        throw new VariableInjectException($varName, 
            VariableInjectException::ERROR_VALUE_UNSERIALIZABLE);
    }
}