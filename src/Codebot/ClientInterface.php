<?php

namespace TaskChecker\Codebot;

interface ClientInterface
{
    /**
     * @param RunScriptTask[] $tasks
     */
    public function execute(array $tasks);

}