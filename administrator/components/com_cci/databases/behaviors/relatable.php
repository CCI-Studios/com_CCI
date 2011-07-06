<?php

/**
 * Relatable Behavior 
 * 
 * The relatable behavior provides a riby-esk has_many/belongs_to relationship model.
 * 
 * @author jbennett
 *
 */
class ComCCIDatabaseBehaviorRelatable extends KDatabaseBehaviorAbstract
{
	
	protected $_has_many;
	protected $_belongs_to;
	protected $_relationships;
	
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		foreach ($config->belongs_to as $belongs_to) {
			$this->belongs_to($belongs_to);
		}
		
		foreach ($config->has_many as $has_many) {
			$this->has_many($has_many);
		}
	}
	
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
				'belongs_to'	=> array(),
				'has_many'		=> array(),
		));
		
		parent::_initialize($config);
	}
	
	public function getMixableMethods(KObject $mixer = null)
	{
		$methods = parent::getMixableMethods($mixer);
		print_r($methods);
		die;
		
		return $methods;
	}
	
	public function belongs_to($name, $column = null, $model = null)
	{
		 if ($column == null) {
		 	$column = "{$name}_id";
		 }
		 
		 if ($model === null) {
		 	$model = $name;
		 }
		 
		 
	}
	
	public function has_many($config = array())
	{
			echo "has many";
			print_r($$config);
			die;
	}
	
	//public function ___get()
}