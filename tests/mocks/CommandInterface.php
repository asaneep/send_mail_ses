<?php

namespace Aws;

/**
 * Mock CommandInterface for testing
 */
interface CommandInterface
{
    public function getName();
    public function toArray();
    public function hasParam($name);
    public function getParam($name);
}

/**
 * Mock Command class for testing
 */
class Command implements CommandInterface
{
    private $name;
    private $params;
    
    public function __construct($name, array $params = [])
    {
        $this->name = $name;
        $this->params = $params;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function toArray()
    {
        return $this->params;
    }
    
    public function hasParam($name)
    {
        return isset($this->params[$name]);
    }
    
    public function getParam($name)
    {
        return isset($this->params[$name]) ? $this->params[$name] : null;
    }
}