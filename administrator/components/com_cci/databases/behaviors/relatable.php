<?php

class ComCCIDatabaseBehaviorRelatable extends KDatabaseBehaviorAbstract
{
	
	protected $_relationships;
	
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		
	}
	
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
					
		));
		
		
		parent::_initialize($config);
	}
	
}