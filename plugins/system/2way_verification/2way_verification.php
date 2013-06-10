<?php
/**
 * @package     Joomla Plugin for two way authetication
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * @package     System.GoogleAuthenticator
 * @since       2.5
 */
require_once 'helper.php';
include_once("lib/GoogleAuthenticator.php");

class plgSystem2way_Verification extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		
		$this->is_enable = $this->params->get('is_enable', false);
		
		if(JFactory::getApplication()->isAdmin() && JFactory::getUser()->id) {
			$this->verified = false;
			if(self::is_verified()) {
				$this->verified = true;
			}
		}
	}

	function onAfterRoute()
	{
		if(JFactory::getApplication()->isSite()) {
			return true;
		}
		
		$input = JFactory::getApplication()->input;
		
		// is it ajax req or not
		if('2way_verification' != $input->get('plugin', '')) {
			return true;
		}
		
		$method = $input->get('method');
		
		echo $this->$method();
		exit;
	}
	
	function is_verified()
	{
		$session = JFactory::getSession();
		$user = $session->get('user') ;
		if(!($user instanceof JUser) ){
			return false;
		}
		if(isset($user->twoway_verification) && !empty($user->twoway_verification)) {
			return true;
		}
		// default
		return false;		
	}
	
	function onAfterRender()
	{
		if(		!($this->is_enable) ||
				!isset($this->verified) || 
				(isset($this->verified) && $this->verified)
		  ) {
			return true;
		}
		
		$buffer      = JResponse::getBody();
		ob_start();
			include_once 'tmpl/key.php';
		$two_way_html = ob_get_contents();
		ob_end_clean();
		
		//$buffer = preg_replace("%<body\s*\w*>([\w\W]*)</body>%i",$two_way_html, $buffer);
		//$buffer = preg_replace('%<body id="minwidth-body">([\w\W]*)</body>%i',$two_way_html, $buffer);
		$buffer = preg_replace('%<body.*>([\w\W]*)</body>%i',$two_way_html, $buffer);		
		JResponse::setBody($buffer);
	}
	
	/**
	 * Check the verification code entered by the user.
	 */
	private function verify() 
	{
		$key = JFactory::getApplication()->input->get('2way');
		$secretkey = $this->params->get('secret_key')->GA_secret;
		
		$g = new GoogleAuthenticator();
		
		$this->verified = (boolean)$g->checkCode($secretkey, $key);
		
		$session = JFactory::getSession();
		$user = $session->get('user') ;
		$user->twoway_verification = $this->verified;
		$session->set('user',$user);
		$redirect_url = JFactory::getApplication()->input->get('redirect','index.php');
		
		JFactory::getApplication()->redirect($redirect_url);
	}
	
	/**
	 * Generate 16 digit secret key
	 */
	private function getSecretKey() {	
		$g = new GoogleAuthenticator();
	    return $g->generateSecret();
	}
	
	/**
	 * Create QR code URL
	 */
	private function getQRcode() {
	    $input = JFactory::getApplication()->input;
	    
	    $desc = $input->get('GA_desc');
	    $key  = $input->get('GA_secret');
	    
	    return GoogleAuthenticationHelper::getQRcode($desc, $key);
	}

}


