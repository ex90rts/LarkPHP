<?php
namespace Lark\Uniqid;

interface DbengineInterface{
    public function insert($type, $id);
    
    public function hasId($id);
    
    public function getTypeCount($type);
}