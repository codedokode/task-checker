<?php 

namespace TaskChecker;

/**
 * Хранит информацию о задаче.
 */
class Task
{
    private $id;
    private $title;
    private $description;

    function __construct($id, $title, $description) 
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function getTitle()
    {
        return $this->title;
    }
    
    public function getDescription()
    {
        return $this->description;
    }
}
