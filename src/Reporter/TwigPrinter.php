<?php 

namespace TaskChecker\Reporter;

use TaskChecker\Reporter\Report;
use TaskChecker\Step\RunScriptStep;
use TaskChecker\Step\Step;
use TaskChecker\Step\StepWithResult;
use TaskChecker\Util\StringUtil;

/**
 * Prints a report with a twig template. Should be used 
 * along with reportPrinter.html.twig template
 */
class TwigPrinter extends Printer
{
    /** @var Twig_Environment */
    private $twig;

    function __construct(\Twig_Environment $twig) 
    {
        $this->twig = $twig;
    }

    public function printReport(Report $report)
    {
        return $this->twig->render('components/reportPrinter.html.twig', [
            'twigPrinter'       =>  $this,
            'report'            =>  $report
        ]);
    }

    public function getMacroNameForStep(Step $step)
    {
        return $this->printStepForClass($step);
    }

    public function getStepResultAsString($result)
    {
        return StringUtil::stringify($result);
    }
    
    public function printStep(Step $step)
    {
        return 'itemStep';
    }

    public function printStepWithResult(StepWithResult $step)
    {
        return 'itemStepWithResult';
    }

    public function printRunScriptStep(RunScriptStep $step)
    {
        return 'itemRunScriptStep';
    }
}
