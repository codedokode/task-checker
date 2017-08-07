<?php 

namespace TaskChecker\Web;

use Symfony\Component\Routing\Generator\UrlGenerator;
use TaskChecker\Problem;

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

    public function getViewProblemUrl(Problem $problem)
    {
        return $this->generate('viewProblem', ['problemId' => $problem->getSlug()]);
    }
}