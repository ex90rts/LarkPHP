<?php
namespace Alcedoo\Uniqid;

interface DbengineInterface{
    public function insert($type, $id);
    
    public function hasId($id);
    
    public function getTypeCount($type);
}