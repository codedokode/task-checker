<?php 

namespace TaskChecker\Web;

use Symfony\Component\Routing\Generator\UrlGenerator;
use TaskChecker\Task;

class TcUrlGenerator 
{
    /** @var UrlGenerator */
    private $urlGenerator;

    function __construct(UrlGenerator $urlGenerator) 
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function generate($name, array $args = [])
    {
        return $this->urlGenerator->generate($name, $args);
    }

    public function getViewTaskUrl(Task $task)
    {
        return $this->generate('viewTask', ['taskId' => $task->getSlug()]);
    }
}