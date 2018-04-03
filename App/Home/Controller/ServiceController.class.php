<?php 
namespace Home\Controller;

class ServiceController extends ComController{
    
    private $nombre = '';
    
    public function __construct($name = 'World') 
	{
		$this->name = $name;
	}
	
    public function greet($name = '') 
	{
		$name = $name?$name:$this->name;
        return 'Hello '.$name.'.';
	}
	
    public function serverTimestamp() 
	{
		return time();
	}
	
	public function myfunc($a=''){
	    return $a;
	}
}