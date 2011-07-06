<?php

class ComCCIDatabaseRowRelated extends KDatabaseRowTable
{
	protected $_has_many;
	protected $_belongs_to;
	
	
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		foreach ($config->belongs_to as $belongs_to) {
			$this->_belongsTo($belongs_to);
		}
		
		foreach ($config->has_many as $has_many) {
			$this->_hasMany($has_many);
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
	
	protected function _belongsTo(KConfig $config)
	{
		$config->append(array(
			'name' 		=> '',
			'lazy'		=> true,
			'id'		=> null,
		))->append(array(
			'column' 	=> $config->name .'_id',
			'table'		=> $config->name,
		));
		
		if (!isset($config->name)) {
			// return error
			return;
		}
		
		$this->_belongs_to[$config->name] = $config;
	}
	
	protected function _hasMany(KConfig $config)
	{
		$config->append(array(
			'name'	=> '',
			'lazy'	=> true,
			'id'	=> null,
		))->append(array(
			'column'	=> $this->_identifier->name.'_id',
			'table'		=> $config->name,
		));
		
		if (!isset($config->name)) {
			// return error
			return;
		}
		
		$this->_has_many[$config->name] = $config;
	}
	
	protected function _loadObject($table, $column, $id, $method = KDatabase::FETCH_ROW)
	{
		if ($id === null)
			return null;
		
		if (!($table instanceof KIdentifier)) {
			if (is_string($table) && strpos($table, '.') === false) {
				$identifier = clone $this->_identifier;
				$identifier->path = array('database', 'table');
				$identifier->name = KInflector::pluralize($table);
			} else {
				$identifier = KFactory::identify($table);
			}
		}
		
		$table = KFactory::get($identifier);
		$query = $table->getDatabase()->getQuery();
		$query->where($column, '=', $id);
		
		return $table->select($query, $method);
	}
	
	public function __get($key)
	{
		if (isset($this->_belongs_to[$key])) {
			$config = $this->_belongs_to[$key];
			
			if (!isset($config->data)) {
				$config->data = $this->_loadObject($config->table, $config->column, $this->{$config->column});
			}
			
			return $config->data;
		}	
		
		if (isset($this->_has_many[$key])) {
			$config = $this->_has_many[$key];
			
			if (!isset($config->data)) {
				$config->data = $this->_loadObject($config->table, $config->column, $this->id, KDatabase::FETCH_ROWSET);
			}
			
			return $config->data;
		}
		
		return parent::__get($key);
	}
}