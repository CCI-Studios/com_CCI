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
	/**
	 * Contains all belongs to relationship configurations.
	 * 
	 * @var array
	 */
	protected $_belongs_to;
	
	/**
	 * Contains all has many relationship configurations.
	 * 
	 * @var array
	 */
	protected $_has_many;
	
	/**
	 * Contains all retrieved relationships
	 * 
	 * @var array
	 */
	protected $_relationships;

	public function __construct(KConfig $config)
	{
		$this->_relationships = array();
		$this->_belongs_to = array();
		$this->_has_many = array();
		
		parent::__construct($config);

		foreach ($config->belongs_to as $belongs_to) {
			$this->belongsTo($belongs_to);
		}
		
		foreach ($config->has_many as $has_many) {
			$this->hasMany($has_many);
		}
	}
	
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
				'belongs_to'	=> array(),
				'has_many'		=> array(),
				'auto_mixing'	=> true,
		));
		
		parent::_initialize($config);
	}
	
	public function getMixableMethods(KObject $mixer = null)
	{
		$methods = array();
		
		foreach ($this->_belongs_to as $belongs) {
			$methods[] = 'get'. ucwords($belongs->name);
		}
		
		foreach($this->_has_many as $many) {
			$methods[] = 'get'. ucwords($many->name);
		}
		
		$methods = array_merge($methods, parent::getMixableMethods($mixer));
		return $methods;
	}
	
	/**
	 * 
	 * 
	 * @param KConfig $config
	 */
	public function belongsTo(KConfig $config)
	{		
		$config->append(array(
			'name'		=> '',
		))->append(array(
			'column'	=> KInflector::pluralize($config->name) ."_{$config->name}_id",
			'table'		=> "admin::com.". KInflector::pluralize($config->name) .".database.table.". KInflector::pluralize($config->name),
		));
		
		$this->_belongs_to[$config->name] = $config;
	}
	
	public function hasMany(KConfig $config) {
		$config->append(array(
			'name'		=> '',
		))->append(array(
			'column'	=> '',
			'table'		=> '',
		));
		
		$this->_has_many[$config->name] = $config;
	}
	
	public function getRelated($key)
	{
		if (isset($this->_relationship[$key])) {
			return $this->_relationships[$key];
		}
		
		if (isset($this->_belongs_to[$key])) {
			$config = $this->_belongs_to[$key];
			
			$this->_relationships[$key] = KFactory::tmp($config->table)
				->select($this->{$config->column}, KDatabase::FETCH_ROW);
			
			return $this->_relationships[$key];
		}
		
		if (isset($this->_has_many[$key])) {
			$config = $this->_has_many[$key];
			
			$table = KFactory::tmp($config->table);
			$query = $table->getDatabase()->getQuery();
			$query->where($config->column, '=', $this->id);
			$this->_relationships[$key] = $table->select($query, KDatabase::FETCH_ROWSET);
			
			return $this->_relationships[$key];
		}
	}
	
	public function __call($method, array $args)
	{
		$parts = KInflector::explode($method);
		if ($parts[0] == 'get' && isset($parts[1])) {
			
			
			$result = $this->getRelated($parts[1]);
			
			if ($result)
				return $result;
		}
		
		return parent::__call($method, $args);
	}	
}