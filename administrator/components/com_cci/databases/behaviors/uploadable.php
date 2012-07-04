<?php
/**
 * @version		$Id $
 * @category	Koowa
 * @package 	Koowa_Database
 * @subpackage 	Behavior
 * @copyright	Copyright (C) 2010-2011 CCI Studios. All rights reserved.
 * @license		GNU GPLv3 <http://www.gnu.org/license/gpl.html>
 * @link		http://ccistudis.com
 *
 */
class ComCCIDatabaseBehaviorUploadable extends KDatabaseBehaviorAbstract {
	
	protected $_location;
	protected $_fieldname;
	
	public function __construct(KConfig $config = null)
	{
		jimport('joomla.filesystem.file'); // replace with nooku file options
		parent::__construct($config);
		
		foreach($config as $key=>$value) {
			$this->{'_'.$key} = $value;
		}
	}
	
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
			'location'	=> 'media/uploads/',
			'fieldname'	=> 'filename',
		));
		parent::_initialize($config);
	}
	
	
	protected function _beforeTableInsert(KCommandContext $context)
	{
		return $this->_saveUpload($context);
	}
	
	protected function _beforeTableUpdate(KCommandContext $context)
	{		
		return $this->_saveUpload($context);
	}

	protected function _saveUpload(KCommandContext $context)
	{
		$post = $context->data;
		$file = KRequest::get('FILES.'.$this->_fieldname.'_upload', 'raw');
		
		// delete images if requested or if a new file is uploaded
		if (isset($post->{$this->_fieldname.'_delete'}) || ($file && $file['error'] != 4)) {
			$this->deleteFile($post->{$this->_fieldname});
			$post->{$this->_fieldname} = null;
		}

		if (!$file) { return true; }

		// cancel if there is an error and there is a file
		if ($file['error'] !== 0 && $file['error'] !== 4) {
			JError::raiseWarning('300', 'Error uploading file.');
			return false;
		}
		
		// no file to save
		if ($file['error'] === 4)
			return true;
		
		list($filename, $extension) = $this->storeNewFile($file);
		if ($filename === false) {
			JError::raiseWarning('300', 'Error moving file into media folder');
			return false;
		}
		$post->{$this->_fieldname} = $filename.$extension;
		
		return true;
	}
	
	
	protected function deleteFile($filename)
	{
		if (JFile::exists(JPATH_ROOT .'/'. $this->_location.$filename)) {
			JFile::delete(JPATH_ROOT .'/'. $this->_location.$filename);
		}
	}
	
	protected function storeNewFile($fileinfo)
	{
		$extension 	= JFile::getExt($fileinfo['name']);
		$src		= $fileinfo['tmp_name'];
		
		do {
			$dest = time().rand(0,100);
		} while (JFile::exists(JPATH_ROOT .'/'. $this->_location.$dest.'.'.$extension));
		
		if (!JFile::upload($src, JPATH_ROOT .'/'. $this->_location.$dest.'.'.$extension)) {
			return false;
		}
		return array($dest,'.'.$extension);
	}
	
}