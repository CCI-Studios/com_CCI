<?php

class ComCCIDatabaseBehaviorNotifiable extends KDatabaseBehaviorAbstract
{
	/**
	 * Collection of emails.
	 * 
	 * @var array
	 */
	protected $_emails;
	
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->_emails = array();
		$this->_emails['insert'] = array();
		$this->_emails['update'] = array();
		$this->_emails['delete'] = array();
		$this->_emails['select'] = array();
		
		foreach($config->emails as $email) {
			$this->addEmail($email);
		}
	}
	
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
				'emails' => array(),
		));
	
		parent::_initialize($config);
	}
	
	/**
	 * Adds an email to the system. 
	 * Emails are auto triggered on table events, named emails can be triggered manually. Named emails with the same name overwrite each other. 
	 * 
	 * Emails added after an event has occured are not retroactivly sent.
	 * 
	 * @param $type			Type of email to send, can be insert, update, delete, select or named
	 * @param $name			Name of email. Used internally and for calling named emails.
	 * @param $subject		Subject of email
	 * @param $file			Filename of layout to use as tempalte  
	 * @param $to			Static email address to send the email to
	 * @param $dynamicTo	Dynamic email address to send to.
	 * @param $fromName		Name of sender. Defaults to fromname from Joomla! config
	 * @param $fromEmail	Email of sender. Defaults to fromemail from Joomla! config
	 * @param $html			Send email as HTML email. Defaults to true
	 * @param $successMessage Message to enqueue if notification is successful
	 * @param $failMessage	Mesage to enqueue if notification is not successful
	 *  
	 * @return void
	 */
	public function addEmail($config)
	{
		$params = JFactory::getConfig();
		
		$config = new KConfig($config);
		$config->append(array(
			'type'		=> 'named',
			'to'		=> null,
			'dynamicTo'	=> null,
			'fromName'	=> $params->getValue('config.mailfrom'),
			'fromEmail'	=> $params->getValue('config.fromname'),
			'html'		=> true,
			'successMessage'	=> JText::_('Notification sent successfully'),
			'failMessage'		=> JText::_('Error sending notification')
		))->append(array(
			'name'		=> $config->type,
		))->append(array(
			'file'		=> "email_{$config->name}",
			'subject'	=> $config->name,
		));
		
		if ($config->type === 'named') {
			$this->_emails['named'][$config->name] = $config;
		} else {
			$this->_emails[$config->type][] = $config;
		}
	}
	
	/**
	 * Sends a named email.
	 *
	 * @param string $name Name of the email you want to send
	 */
	public function sendNamed($name)
	{
		if (isset($this->_emails['named'][$name])) {
			$this->_sendEmail($this->_emails['named'][$name], $this->getData());
		} else {
			JError::raiseWarning(100, JText::_('Error sending notification: Named email doesn\'t exist'));
		}
	}
	
	protected function _afterTableInsert(KCommandContext $context)
	{
		$this->_sendEmails('insert', $context);
	}
	
	protected function _afterTableUpdate(KCommandContext $context)
	{
		$this->_sendEmails('update', $context);
	}
	
	protected function _afterTableDelete(KCommandContext $context)
	{
		$this->_sendEmails('delete', $context);
	}
	
	protected function _afterTableSelect(KCommandContext $context)
	{
		$this->_sendEmails('select', $context);
	}
	
	protected function _sendEmails($type, KCommandContext $context)
	{
		foreach($this->_emails[$type] as $email) {
			$this->_sendEmail($email, $context->data->toArray());
		}
	}
	
	protected function _sendEmail($email, $data)
	{		
		$identifier = clone $this->getMixer()->getIdentifier();
		$identifier->path = array('view');
		$identifier->name = KInflector::pluralize($identifier->name);
		
		$view = KFactory::get('admin::com.default.template.default');
		$view->loadIdentifier("$identifier.{$email->file}", $data);
		
		$mailer = JFactory::getMailer();
		$mailer->setSender(array($email->fromEmail, $email->fromName));
		if ($email->to)
			$mailer->addRecipient($email->to);
		if ($email->dynamicTo)
			$mailer->addRecipient($data->{$email->dynamicTo});
		$mailer->setSubject($email->subject);
		$mailer->setBody($view->render());
		$mailer->isHtml($email->html);
		
		if ($mailer->send()) {
			if ($email->successMessage) {
				KFactory::get('lib.joomla.application')->enqueueMessage($email->succuessMessage);
			}
		} else {
			if ($email->failMessage) {
				JError::raiseWarning(100, $email->failMessage);
			}
		}
	}
}