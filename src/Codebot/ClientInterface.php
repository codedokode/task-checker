<?php

namespace Codebot;

interface ClientInterface
{
    /**
     * @param RunScriptTask[] $tasks
     */
    public function execute(array $tasks);

}