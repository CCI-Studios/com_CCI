<?php

class ComCCIDatabaseBehaviorNotifiable extends KDatabaseBehaviorAbstract
{
	protected $_emails;
	
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$params = JFactory::getConfig();
		$this->_emails = array();
		$this->_emails['insert'] = array();
		$this->_emails['update'] = array();
		$this->_emails['delete'] = array();
		$this->_emails['select'] = array();
		
		foreach($config->emails as $email) {
			$email->append(array(
				'event'		=> 'insert',
				'to'		=> null,
				'dynamicTo'	=> null,
				'mailFrom'	=> $params->getValue('config.mailfrom'),
				'fromName'	=> $params->getValue('config.fromname'),
				'html'		=> true,
			))->append(array(
				'email'		=> "email_{$email->event}",
				'subject'	=> "{$email->event} Occured",
			));

			$this->_emails[$email->event][] = $email;
		}
	}
	
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
			'emails' => array()
		));
		
		parent::_initialize($config);
	}
	
	protected function _afterTableInsert(KCommandContext $context)
	{
		foreach($this->_emails['insert'] as $email) {
			$this->_sendEmail($email, $context->data, $context->caller);
		}
	}
	
	protected function _afterTableUpdate(KCommandContext $context)
	{
		foreach($this->_emails['update'] as $email) {
			$this->_sendEmail($email, $context->data, $context->caller);
		}
	}
	
	protected function _afterTableDelete(KCommandContext $context)
	{
		foreach($this->_emails['delete'] as $email) {
			$this->_sendEmail($email, $context->data, $context->caller);
		}
	}
	
	protected function _afterTableSelect(KCommandContext $context)
	{
		foreach($this->_emails['select'] as $email) {
			$this->_sendEmail($email, $context->data, $context->caller);
		}
	}
	
	protected function _sendEmail($email, $data, $caller) {
		$identifier = clone $caller->getIdentifier();
		$identifier->path = array('view');
		
		$view = KFactory::get('admin::com.default.template.default');
		$view->loadIdentifier("$identifier.{$email->email}", $data->toArray());
		
		$mailer = JFactory::getMailer();
		$mailer->setSender(array($email->mailFrom, $email->fromName));
		if ($email->to)
			$mailer->addRecipient($email->to);
		if ($email->dynamicTo)
			$mailer->addRecipient($data->{$email->dynamicTo});
		$mailer->setSubject($email->subject);
		$mailer->setBody($view->render());
		$mailer->isHtml($email->html);
		if ($mailer->send()) {
			echo "email sent";
		} else {
			echo "counlding send";
			die;
		}
	}
}