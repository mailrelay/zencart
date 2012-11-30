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
	
	protected $_curl;
	
	/**
	 * @var string $_session
	 */
	protected $_session;

	protected function initCurl($host)
	{
        	if (substr($host, 0, 7)!="http://") $url = "http://";
                else $url = "";
                $url = $url.$host."/ccm/admin/api/version/2/&type=json";
                $curl = curl_init($url);

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POST, 1);

		$current_version = PROJECT_VERSION_NAME . ' ' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
		$headers = array('X-Request-Origin: Zencart|0.1b|'.$current_version);

                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$this->_curl = $curl;
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
		$this->initCurl($url);
		
                $params = array(
                        'function' => 'doAuthentication',
                                'username' => $username,
                                'password' => $password
                ); 

                curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $params);
                curl_setopt($this->_curl, CURLOPT_VERBOSE, TRUE);

                $current_version = PROJECT_VERSION_NAME . ' ' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
                $headers = array('X-Request-Origin: Zencart|0.1b|'.$current_version);

                curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($this->_curl);

                $jsonResult = json_decode($result);

                if (!$jsonResult->status) {
                	$this->_apiKey = "";
			return false;
                } else {
                	$this->_apiKey = $jsonResult->data;
			return true;
                }
	}


        /**
        * Executes an API call against the API
        * 
        * @param array $params Array with the API methods to execute
        * @return object 
        */
        public function APICall( $params = array())
        {
		$params["apiKey"] = $this->_apiKey;
                curl_setopt( $this->_curl, CURLOPT_POSTFIELDS, $params );

                $current_version = PROJECT_VERSION_NAME . ' ' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
                $headers = array('X-Request-Origin: Zencart|0.1b|'.$current_version);

                curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec( $this->_curl );
                $jsonResult = json_decode($result);
         
                if ($jsonResult->status) {
                        return $jsonResult->data;
                } else {
                        return NULL;
                }
        }

        // check one user
        public function getUser($email)
        {
                if (!$this->_apiKey)
                {
                        $this->_apiKey=$this->getApiKey();
                }
                $params = array(
                        'function' => 'getSubscribers',
                                'email'=>$email,
                                'apiKey'=>$this->_apiKey
                );
                $data = $this->APICall($params);
                if ($data==NULL) return new StdClass;
                else return $data[0];

        }

    	public function addMailrelayUser( $email = '', $username = '', array $groups = array())
    	{
        	if (!$this->_apiKey) return false;

        	$params = array(
                	'function' => 'addSubscriber',
                	'apiKey' => $this->_apiKey,
                	'email' => $email,
                	'name' => $username,
                	'groups' => $groups
            	);

            	$post = http_build_query($params);
            	curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);

                $current_version = PROJECT_VERSION_NAME . ' ' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
                $headers = array('X-Request-Origin: Zencart|0.1b|'.$current_version);

            	$result = curl_exec($this->_curl);
            	$jsonResult = json_decode($result);

            	if ( $jsonResult->status ) {
                	return 1;
	    	}
	    	else
	    	{
			return 0;
	    	}
	}

    	/**
    	 * Update an already existing Mailrelay user
     	* 
     	* @param integer $id User id in the Mailrelay system
     	* @param string $email User email from the vBulletin database
     	* @param string $username Username from the vBulletin database
     	* @param array $groups Selected groups to sync the user to
     	* return integer
     	*/
        public function updateMailrelayUser($user_id, $user_email, $user_name, array $user_groups=array())
        {
                if (!$this->_apiKey) return false;

                $params = array(
                        'function' => 'updateSubscriber',
                        'apiKey' => $this->_apiKey,
                        'id' => $user_id,
                        'email' => $user_email,
                        'name' => $user_name,
                        'groups' => $user_groups
                );

                $post = http_build_query($params);
                curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);

                $current_version = PROJECT_VERSION_NAME . ' ' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
                $headers = array('X-Request-Origin: Zencart|0.1b|'.$current_version);

                curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($this->_curl);
                $jsonResult = json_decode($result);

                if ( $jsonResult->status ) {
                        return 1;
                }
                else {
                        return 0;
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
		if (!$this->_apiKey) return false;
		
		try {
			// Search for subscribers with the same email address
			if ( !is_null( $oldMail ) )
			{
				$user = $this->getUser($oldMail);
			}
			else
			{
				$user = $this->getUser($email);
			}
		} catch (Exception $e) {
			
			$this->_errors[] = $e->getMessage();
			
			return false;
		}
		
		try {
			// Check if we've found subscriber with the same email address and add/update them
			if (empty($user)) {
				$this->addMailrelayUser($email, $name, array(
					$group
				));
			} else {
				$this->updateMailrelayUser($user->id, $email, $name, array(
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
     	* Remove an already existing Mailrelay user
     	* 
     	* @param integer $id User id in the Mailrelay system
     	* return integer
     	*/
        public function deleteSubscriber($email)
        {
                if (!$this->_apiKey) return false;

                $params = array(
                        'function' => 'deleteSubscriber',
                        'apiKey' => $this->_apiKey,
                        'email' => $email
                );

                $post = http_build_query($params);
                curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);

                $current_version = PROJECT_VERSION_NAME . ' ' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
                $headers = array('X-Request-Origin: Zencart|0.1b|'.$current_version);

                curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($this->_curl);
                $jsonResult = json_decode($result);

                if ( $jsonResult->status ) {
                        return 1;
                }
                else {
                        return 0;
                }
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
        * Prepare a json of groups obtained from the API and turn it into an array
        * 
        * @param json $rawGroups A json of groups obtained from the API
        * @return array 
        */
        public function apiGroupsToArray( $rawGroups ) 
        {
                $groupSelect = array();

                foreach ( $rawGroups AS $group ) {
                        if ( $group->enable == 1 AND $group->visible == 1) {
                                $groupSelect[$group->id] = $group->name;
                        }
                }
                return $groupSelect;
	}

        /**
        * Get MR groups
        * 
        * @return object 
        */
        public function getMailrelayGroups()
        {
                if (!$this->_apiKey) return false;

                if ($this->_apiKey)
                {
                        $params = array(
                                'function' => 'getGroups',
                                    'apiKey' => $this->_apiKey
                        );
        
                        $data = $this->APICall($params);

                        if ( ($data == NULL) || (!(count( $this->apiGroupsToArray( $data ) ) > 0)) ) 
                        {
                                return array();
                        }
                        else {
                                $groups = $this->apiGroupsToArray( $data );
                                $totales = array();
                                foreach($groups as $key=>$value)
                                {
                                        $item["value"]=$key;
                                        $item["label"]=$value;
                                        $totales[] = $item;
                                }
                                return $totales;
                        }
                }
                else
                {
                        // invalid API key
                        return false;
                }
        }
	
	/**
	 * Get MR groups
	 * 
	 * @return array|boolean
	 */
	public function getGroups()
	{
		try {
			// Get groups and return in the format used by zen cart for select elements
			$groups = $this->getMailrelayGroups();
			
			$data = array();
			foreach ($groups as $group) {
				$data[] = array(
					'text' => $group['label'], 
					'id' => $group['value']
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
