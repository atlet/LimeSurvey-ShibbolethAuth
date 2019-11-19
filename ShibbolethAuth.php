<?php

class ShibbolethAuth extends AuthPluginBase {

    protected $storage = 'DbStorage';
    static protected $description = 'Shibboleth authentication';
    static protected $name = 'Shibboleth';
    public $atributi = '';
    public $mail = '';
    public $displayName = '';
    protected $settings = array(
			'authuserid' => array(
            'type' => 'string',
            'label' => 'Shibboleth attribute of User ID (eg. eduPersonPrincipalName)',
            'default' => 'eduPersonPrincipalName',
        ),
            'authusergivenName' => array(
            'type' => 'string',
            'label' => 'Shibboleth attribute of User first name (eg. givenName)',
            'default' => 'givenName',
        ),
            'authusergivenSurname' => array(
            'type' => 'string',
            'label' => 'Shibboleth attribute of User surname (eg. sn)',
            'default' => 'sn',
        ),
            'mailattribute' => array(
            'type' => 'string',
            'label' => 'Shibboleth attribute of User email address (eg. mail)',
            'default' => 'mail',
		),
            'logoffurl' => array(
            'type' => 'string',
            'label' => 'Redirecting url after LogOff',
            'default' => 'https://my.example.com/Account/Logoff',
		),
            'is_default' => array(
            'type' => 'checkbox',
            'label' => 'Check to make default authentication method (this disable Default LimeSurvey authentification by database)',
            'default' => false,
        ),
            'autocreateuser' => array(
            'type' => 'checkbox',
            'label' => 'Automatically create user if not exists',
            'default' => true,
        ),
            'permission_create_survey' => array(
            'type' => 'checkbox',
            'label' => 'Permission create survey',
            'default' => false,
        )
    );

    public function __construct(\LimeSurvey\PluginManager\PluginManager $manager, $id) {
        parent::__construct($manager, $id);

        $this->subscribe('beforeLogin');
        $this->subscribe('newUserSession');
		$this->subscribe('afterLogout');
    }

    public function beforeLogin() {
	    	// Doesn't return any value to $identity, it fails, always
	    	// Do nothing if this user is not ShibbolethAuth type
		/*
		$identity = $this->getEvent()->get('identity');
		if ($identity->plugin != 'ShibbolethAuth') 
		{
			return;
		}
		*/

		$authuserid = $this->get('authuserid');
		$authusergivenName = $this->get('authusergivenName');
		$authusergivenSurname = $this->get('authusergivenSurname');
		$mailattribute = $this->get('mailattribute');

		if (!empty($authuserid) && isset($_SERVER[$authuserid]))
		{
			$sUser=$_SERVER[$authuserid];
			
			// Possible mapping of users to a different identifier
			$aUserMappings=$this->api->getConfigKey('auth_webserver_user_map', array());
            if (isset($aUserMappings[$sUser])) 
            {
               $sUser = $aUserMappings[$sUser];
            }
						
			// If is set "autocreateuser" option then create the new user
            if($this->get('autocreateuser',null,null,$this->settings['autocreateuser']['default']))
            {
                $this->setUsername($sUser);
				$this->displayName = $_SERVER[$authusergivenName].' '.$_SERVER[$authusergivenSurname];
				
				if($_SERVER[$mailattribute] && $_SERVER[$mailattribute] != '')
				{
					$this->mail = $_SERVER[$mailattribute];
				}
				else $this->mail = 'noreply@my.example.com';
				
                $this->setAuthPlugin(); // This plugin handles authentication, halt further execution of auth plugins
            }
            elseif($this->get('is_default',null,null,$this->settings['is_default']['default']))
            {
                throw new CHttpException(401,'Wrong credentials for LimeSurvey administration: "' . $sUser . '".');
            }
		}
    }

    public function newUserSession() {
        $sUser = $this->getUserName();
        $oUser = $this->api->getUserByName($sUser);

        if (is_null($oUser)) {
            // Create new user
            $oUser = new User;
            $oUser->users_name = $sUser;
            $oUser->password = hash('sha256', createPassword());
            $oUser->full_name = $this->displayName;
            $oUser->parent_id = 1;
            $oUser->email = $this->mail;

            if ($oUser->save()) {
                if ($this->get('permission_create_survey', null, null, false)) {
                    $data = array(
                        'entity_id' => 0,
                        'entity' => 'global',
                        'uid' => $oUser->uid,
                        'permission' => 'surveys',
                        'create_p' => 1,
                        'read_p' => 0,
                        'update_p' => 0,
                        'delete_p' => 0,
                        'import_p' => 0,
                        'export_p' => 0
                    );

                    $permission = new Permission;
                    foreach ($data as $k => $v)
                        $permission->$k = $v;
                    $permission->save();
                }


                $this->setAuthSuccess($oUser);
                return;
            } else {
                $this->setAuthFailure(self::ERROR_USERNAME_INVALID);
                return;
            }

            return;
        } else { // The user alredy exists
            $this->setAuthSuccess($oUser);
        }
    }

	public function afterLogout()
    {
		$logoffurl = $this->get('logoffurl');
		
		if (!empty($logoffurl))
		{
			// Logout Shibboleth
			header("Location: " . $logoffurl);
			die();
		}
    }
}


