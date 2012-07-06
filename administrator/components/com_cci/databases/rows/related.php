<?php

/**
 * Provides methods to connect to related rows/rowsets
 *
 * @author jbennett
 */

class ComCCIDatabaseRowRelated extends KDatabaseRowDefault
{
	protected $_has_one;
	protected $_has_many;
	protected $_belongs_to;

	public function __construct(KConfig $config = null)
	{
		parent::__construct($config);

		$this->_has_one = array();
		$this->_has_many = array();
		$this->_belongs_to = array();
	}

    /**
     * Adds a has one relationship.
     *
     * $config can contain:
     *
     *
     *
     * @param $name
     * @param null $config
     */
	protected function has_one($name, $config = null)
	{
		$model_identifier = clone $this->getIdentifier();
		$model_identifier->path = array('model');
		$model_identifier->name = KInflector::pluralize($name);

		$config = new KConfig($config);
		$config->append(array(
			'model'	        => $model_identifier,
			'foreign_key'   => $model_identifier->package .'_'. $name .'_id',
			'local_key'	    => 'id'
		));
		$config->plural = false;

		$this->_has_one[$name] = $config;
	}

	protected function belongs_to($name, $config = null)
	{
		$model_identifier = clone $this->getIdentifier();
		$model_identifier->path = array('model');
		$model_identifier->name = KInflector::pluralize($name);

		$config = new KConfig($config);
		$config->append(array(
			'model'			=> $model_identifier,
			'foreign_key'	=> 'id',
			'local_key'		=> $model_identifier->package .'_'. $name .'_id',
		));
		$config->plural = false;

		$this->_belongs_to[$name] = $config;
	}

	/***
	 * Establishes a has many relationship with a related table.
	 *
	 */
	protected function has_many($name, $config = null)
	{
		$model_identifier = clone $this->getIdentifier();
		$model_identifier->path = array('model');
		$model_identifier->name = KInflector::pluralize($name);

		$config = new KConfig($config);
		$config->append(array(
			'model'			=> $model_identifier,
			'foreign_key'	=> $model_identifier->package .'_'. $this->getIdentifier()->name .'_id',
			'local_key'		=> 'id'
		));
		
		$config->plural = true;
		$this->_has_many[$name] = $config;
	}

	public function __get($key)
	{
		if (isset($this->_has_one[$key])) {
			$config = $this->_has_one[$key];
			$this->_getRelated($config);
			return $this->_has_one[$key]->item;
		} elseif (isset($this->_has_many[$key])) {
			$config = $this->_has_many[$key];
			$this->_getRelated($config);
			return $config->items;
		} elseif (isset($this->_belongs_to[$key])) {
			$config = $this->_belongs_to[$key];
			$this->_getRelated($config);
			return $config->item;
		}

		return parent::__get($key);
	}

	protected function _getRelated(KConfig $config) {
		if (isset($config->item) || isset($config->items)) {
			return;
		}

		$model = $this->getService($config->model);
		$model->set($config->foreign_key, $this->{$config->local_key});

		if ($config->plural) {
			$config->items = $model->getList();
		} else {
			$config->item = $model->getItem();
		}
	}
}