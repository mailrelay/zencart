<?php

if (! defined('IS_ADMIN_FLAG')) {
	die('Illegal Access');
}

/**
 * MR Sync
 * 
 * Zen Cart + MailRelay.es subscribers sync class
 * 
 * @author ConsultorPC.com
 * @version 1.0
 */
class MRSync
{
	/**
	 * @var array $_errors
	 */
	protected $_errors;
	
	/**
	 * @var SoapClient $_soapClient
	 */
	protected $_soapClient;
	
	/**
	 * @var string $_session
	 */
	protected $_session;
	
	public function __construct()
	{
		// Check if soap extension is lodaded
		if (! $this->checkSoapExtension()) {
			throw new Exception('SOAP extension is not loaded.');
		}
	}
	
	/**
	 * Get errors
	 * 
	 * @return array
	 */
	public function getErrors()
	{
		return $this->_errors;
	}
	
	/**
	 * Clear current erros
	 *
	 * @return void
	 */
	public function clearErrors()
	{
		$this->_errors = array();
	}
	
	/**
	 * Login on MR API
	 * 
	 * @param string $url API URL: usually http://url.com/ccm/admin/api/version/2/wsdl
	 * @param string $username
	 * @param string $password
	 * @return boolean
	 */
	public function login($url, $username, $password)
	{
		$current_version = PROJECT_VERSION_NAME . ' ' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
		$headers = array('X-Request-Origin: Zencart|0.1b|'.$current_version);
		$context = stream_context_create(array('http'=>array('header'=>$headers)));

		$this->_soapClient = new SoapClient($url, array(
			'compression' => SOAP_COMPRESSION_ACCEPT,
			'stream_context'=>$context
		));

		try {
			$this->_session = $this->_soapClient->doAuthentication($username, $password);
			
			return true;
		} catch (Exception $e) {
			
			$this->_errors[] = $e->getMessage();
			
			return false;
		}
	}
	
	/**
	 * Sync subscriber
	 * 
	 * @param string $email
	 * @param string $name
	 * @param integer $group
	 * @param string $oldMail Optional parameter, in case you want to sync subscriber by updating his old address
	 * @return boolean
	 */
	public function syncSubscriber($email, $name, $group, $oldMail = null)
	{
		// Check if we are logged in and have session/soap client
		if (! isset($this->_session) && ! isset($this->_soapClient)) {
			return false;
		}
		
		try {
			// Search for subscribers with the same email address
			if ( !is_null( $oldMail ) )
			{
				$search = $this->_soapClient->getSubscribers($this->_session, null, null, null, $oldMail);
			}
			else
			{
				$search = $this->_soapClient->getSubscribers($this->_session, null, null, null, $email);
			}
		} catch (Exception $e) {
			
			$this->_errors[] = $e->getMessage();
			
			return false;
		}
		
		try {
			// Check if we've found subscriber with the same email address and add/update them
			if (empty($search)) {
				$this->_soapClient->addSubscriber($this->_session, $email, $name, array(
					$group
				));
			} else {
				$this->_soapClient->updateSubscriber($this->_session, $search[0]['id'], $email, $name, array(
					$group
				));
			}
		} catch (Exception $e) {
			
			$this->_errors[] = $e->getMessage();
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Sync all customers with the specified group
	 * 
	 * @param integer $group
	 * @return boolean
	 */
	public function syncAllCustomers($group)
	{
		global $db;
		
		// Search all customers and sync them individually
		$query = 'SELECT CONCAT( customers_firstname , " " , customers_lastname ) AS name , customers_email_address AS email FROM ' . TABLE_CUSTOMERS;
		$result = $db->Execute($query);
		
		while (! $result->EOF) {
			
			$sync = $this->syncSubscriber($result->fields['email'], $result->fields['name'], $group);
			
			if (! $sync) {
				return false;
			}
			
			$result->MoveNext();
		}
		
		return true;
	}
	
	/**
	 * Get MR groups
	 * 
	 * @return array|boolean
	 */
	public function getGroups()
	{
		// Check if we are logged in and have session/soap client
		if (! isset($this->_session) && ! isset($this->_soapClient)) {
			return false;
		}
		
		try {
			// Get groups and return in the format used by zen cart for select elements
			$groups = $this->_soapClient->getGroups($this->_session);
			
			$data = array();
			foreach ($groups as $group) {
				$data[] = array(
					'id' => $group['id'], 
					'text' => $group['name']
				);
			}
			
			return $data;
		} catch (Exception $e) {
			
			$this->_errors[] = $e->getMessage();
			
			return false;
		}
	}
	
	/**
	 * Check if soap extension is loaded
	 * 
	 * @return boolean
	 */
	public function checkSoapExtension()
	{
		return extension_loaded('soap');
	}
}
