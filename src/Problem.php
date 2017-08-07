<?php 

namespace TaskChecker;

/**
 * Представляет информацию об одном отдельном задании
 */
class Problem 
{
    /** @var string Уникальный id задачи */
    public $id;

    /** 
     * @var string Относительная ссылка на страницу с задачей, например
     *              '/level1/lesson-about-variables#problem-hello-world'
     */
    public $relativeUrl;

    public $name;

    /** Содержит описание в формате HTML */
    public $description;

    /** Содержит образец кода, который можно взять за основу */
    public $codeSample = null;

    /** Содержит ссылку на образец кода, который можно взять 
        за основу */
    public $codeSampleUrl = null;

    /** Содержит массив подсказок в формате HTML */
    public $hints = [];

    /** Содержит массив HTML строк с примерами входных и 
        выходных данных */
    public $examples = [];

    public function __construct($id, $name, $description) 
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSlug()
    {
        return $this->id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getDescription()
    {
        return $this->description;
    }

    public function getRelativeUrl()
    {
        return $this->relativeUrl;
    }
    
    public function getCodeSample()
    {
        return $this->codeSample;
    }

    public function getCodeSampleUrl()
    {
        return $this->codeSampleUrl;
    }
    
    public function getHints()
    {
        return $this->hints;
    }
    
    public function getExamples()
    {
        return $this->examples;
    }
}
