<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 	Kay Strobach (typo3@kay-strobach.de)
*  (c) 2009 	Ulrich Wuensche (wuensche@drwuensche.de),
*
*  All rights reserved
*
*  This script is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Based on B-Net1 Piwik plugin implementation, old piwik plugin and piwik2a
 * Provides Interface to get the new piwiktrackingcode 
 * 
 * Hooks for the 'piwik' extension.
 *
 * @author	Ulrich Wuensche <wuensche@drwuensche.de>
 * @author	Joerg Winter <winter@b-net1.de>
 * @author  Kay Strobach <typo3@kay-strobach.de> 
 */
class tx_Piwik_UserFunc_Footer {

	var $cObj;

	/**
	 * Piwik PHP Tracking Code for generating the tracking image
	 *
	 * @var Tx_Piwik_PiwikApi_PiwikTracker
	 */
	protected $piwikTracker;

	/**
	 * The merged configuration from config.tx_piwik and the local
	 * configuration of the USER object.
	 *
	 * @var array
	 */
	protected $piwikOptions = array();

	/**
	 * If TRUE the asynchronous JavaScript API will be used
	 *
	 * @var bool
	 */
	protected $useAsyncTrackingApi = FALSE;
 
  /**
   * return the state of DNT Header
	 * @return bool
   */
  function isDntHeaderSet() {
		$ret = false;
		if( isset( $_SERVER ) ) {
			foreach( array( 'HTTP_DNT', 'X-Do-Not-Track' ) as $dnt_head ) {
				if( array_key_exists( $dnt_head, $_SERVER ) ) {
					$ret |= intval($_SERVER[$dnt_head]) == 1;
				}
			}
		}
		return $ret;
  }
		
	/**
	 * write piwik javascript right before </body> tag
	 * JS Documentation on http://piwik.org/docs/javascript-tracking/	 
	 * 
	 * Idea piwikTracker.setDownloadClasses( "download" ); should be set to the default download class of TYPO3
	 * Idea Track TYPO3 404 Errors ... http://piwik.org/faq/how-to/#faq_60
	 *
	 * @param string $trackingCode The current content, normally empty.
	 * @param array $localConfig The configuration that is passed to the USER object.
	 * @return string
	 */
	function contentPostProc_output($trackingCode, $localConfig){
		// process the page with these options
		$conf = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_piwik.'];
		$conf = t3lib_div::array_merge_recursive_overrule($conf, $localConfig);
		$beUserLogin = $GLOBALS['TSFE']->beUserLogin;
		
		if ($conf['useAsyncTrackingApi']) {
			$this->useAsyncTrackingApi = TRUE;
		}

		//check wether there is a BE User loggged in, if yes avoid to display the tracking code!
		//check wether needed parameters are set properly
		if ((!$conf['piwik_idsite']) || (!$conf['piwik_host'])) {
			//fetch the js template file, makes editing easier ;)
			$extConf = unserialize($GLOBALS['$TYPO3_CONF_VARS']['EXT']['extConf']['piwik']);
			if($extConf['showFaultyConfigHelp']) {
				$template = t3lib_div::getURL(t3lib_extMgm::extPath('piwik').'Resources/Private/Templates/Piwik/notracker.html');
			} else {
				return '';
			}
		} elseif (($beUserLogin == 1) && (!intval($conf['trackBackendUsers']))) {
			$template = t3lib_div::getURL(t3lib_extMgm::extPath('piwik').'Resources/Private/Templates/Piwik/notracker_beuser.html');
		}else {
		  if ( $this->isDntHeaderSet() ) {
				$template = t3lib_div::getURL(t3lib_extMgm::extPath('piwik').'Resources/Private/Templates/Piwik/notracker_dnt.html');
			} else {
			//fetch the js template file, makes editing easier ;)
				if ($this->useAsyncTrackingApi) {
					$template = t3lib_div::getURL(t3lib_extMgm::extPath('piwik').'Resources/Private/Templates/Piwik/tracker_async.html');
				} else {
					$template = t3lib_div::getURL(t3lib_extMgm::extPath('piwik').'Resources/Private/Templates/Piwik/tracker.html');
				}
			}
		}
		
		//make options accessable in the whole class
		$this->piwikOptions = $conf;

		$this->initializePiwikTracker();

		//build trackingCode
		$trackingCode .= $this->getPiwikEnableLinkTracking();
		$trackingCode .= $this->getPiwikDomains();
		$trackingCode .= $this->getLinkTrackingTimer();
		$trackingCode .= $this->getPiwikSetDownloadExtensions();
		$trackingCode .= $this->getPiwikAddDownloadExtensions();
		$trackingCode .= $this->getDocumentTitleJS();
		$trackingCode .= $this->getPiwikTrackGoal();
		$trackingCode .= $this->getPiwikSetIgnoreClasses();
		$trackingCode .= $this->getPiwikSetDownloadClasses();
		$trackingCode .= $this->getPiwikSetLinkClasses();
		$trackingCode .= $this->getPiwikCustomVariables();

		if (!$this->useAsyncTrackingApi) {
			$trackingCode .= "\t\t".'piwikTracker.trackPageView();';
		}
		
		//replace placeholders
		//currently the function $this->getPiwikHost() is not called, because of piwikintegration?!
		$template = str_replace('###TRACKEROPTIONS###',$trackingCode        ,$template);
		$template = str_replace('###HOST###'          ,$conf['piwik_host']  ,$template);
		$template = str_replace('###IDSITE###'        ,$conf['piwik_idsite'],$template);
		$template = str_replace('###BEUSER###'        ,$beUserLogin         ,$template);

		if (strlen($this->piwikOptions['trackGoal'])) {
			$template = str_replace('###TRACKING_IMAGE_URL###', htmlentities($this->piwikTracker->getUrlTrackGoal($this->piwikOptions['trackGoal'])), $template);
		} else {
			$template = str_replace('###TRACKING_IMAGE_URL###', htmlentities($this->piwikTracker->getUrlTrackPageView()), $template);
		}

		if (isset($this->piwikOptions['includeJavaScript']) && !(bool)$this->piwikOptions['includeJavaScript']) {
			$template = t3lib_parsehtml_proc::substituteSubpart($template, '###JAVASCRIPT_INCLUDE###', '');
		}

		return $template; 
	}

	/**
	 * a stub for backwards compatibility with extending classes that might use it
	 *
	 * @return	bool		always false
	 */
	function is_backend() {
		return false;
	}
		/**
	 * Generates piwikTracker.trackGoal javascript code
	 *
	 * @return	string		piwikTracker.trackGoal javascript code
	 */
	function getPiwikTrackGoal() {
		if (strlen($this->piwikOptions['trackGoal'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["trackGoal", ' . $this->piwikOptions['trackGoal'] . ']);' . "\n";
			} else {
				return 'piwikTracker.trackGoal(' . $this->piwikOptions['trackGoal'] . ');' . "\n";
			}
		}
		return '';
	}

	/**
	 * Returns the name of the current action (e.g. the current page
	 * title) or an empty string if no title was configured
	 *
	 * @return string the current action name
	 */
	function getPiwikActionName() {

		if ((strtoupper($this->piwikOptions['actionName']) == 'TYPO3') && !($this->piwikOptions['actionName.'])) {
			return $GLOBALS['TSFE']->cObj->data['title'];
		}

		if (strlen($this->piwikOptions['actionName'])) {
			$cObject = t3lib_div::makeInstance('tslib_cObj');
			$actionName = $cObject->stdWrap($this->piwikOptions['actionName'], $this->piwikOptions['actionName.']);
			return $actionName;
		}

		return '';
	}

	/**
	 * Generates piwikTracker.setDocumentTitle javascript code
	 *
	 * @return string piwikTracker.setDocumentTitle javascript code
	 */
	function getDocumentTitleJS() {
		$action = $this->getPiwikActionName();

		if (strlen($action)) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setDocumentTitle", "' . $action . '"]);' . "\n";
			} else {
				return 'piwikTracker.setDocumentTitle("' . $action . '");' . "\n";
			}
		}

		return '';
	}

	/**
	 * Generates piwikTracker.setDownloadExtensions javascript code
	 *
	 * @return	string		piwikTracker.setDownloadExtensions javascript code
	 */
	function getPiwikSetDownloadExtensions() {
		if (strlen($this->piwikOptions['setDownloadExtensions'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setDownloadExtensions", "' . $this->piwikOptions['setDownloadExtensions'] . '"]);' . "\n";
			} else {
				return 'piwikTracker.setDownloadExtensions( "' . $this->piwikOptions['setDownloadExtensions'] . '" );' . "\n";
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.addDownloadExtensions javascript code
	 *
	 * @return	string		piwikTracker.addDownloadExtensions javascript code
	 */
	function getPiwikAddDownloadExtensions() {
		if (strlen($this->piwikOptions['addDownloadExtensions'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["addDownloadExtensions", "' . $this->piwikOptions['addDownloadExtensions'] . '"]);' . "\n";
			} else {
				return 'piwikTracker.addDownloadExtensions( "' . $this->piwikOptions['addDownloadExtensions'] . '" );' . "\n";
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setDomains javascript code
	 *
	 * @return	string		piwikTracker.setDomains javascript code
	 */
	function getPiwikDomains() {
		if (strlen($this->piwikOptions['setDomains'])) {
			$hosts = t3lib_div::trimExplode(',', $this->piwikOptions['setDomains']);
			for ($i=0; $i<count($hosts); $i++) {
				$hosts[$i] = '"'.$hosts[$i].'"';
			}
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setDomains", [' . implode(', ', $hosts) . ']]);' . "\n";
			} else {
				return 'piwikTracker.setDomains([' . implode(', ', $hosts) . ']);' . "\n";
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setLinkTrackingTimer javascript code
	 *
	 * @return	string		piwikTracker.setLinkTrackingTimer javascript code
	 */
	function getLinkTrackingTimer() {
		if (strlen($this->piwikOptions['setLinkTrackingTimer'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setLinkTrackingTimer", ' . $this->piwikOptions['setLinkTrackingTimer'] . ']);' . "\n";
			} else {
				return 'piwikTracker.setLinkTrackingTimer(' . $this->piwikOptions['setLinkTrackingTimer'] . ');' . "\n";
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.enableLinkTracking javascript code
	 *
	 * @return	string		piwikTracker.enableLinkTracking javascript code
	 */
	function getPiwikEnableLinkTracking() {
		if ($this->piwikOptions['enableLinkTracking'] == '0') {
			return '';
		}

		if ($this->useAsyncTrackingApi) {
			return '_paq.push(["enableLinkTracking"]);' . "\n";
		} else {
			return 'piwikTracker.enableLinkTracking();' . "\n";
		}
	}

	/**
	 * Generates piwikTracker.setIgnoreClasses javascript code
	 *
	 * @return	string		piwikTracker.setIgnoreClasses javascript code
	 */
	function getPiwikSetIgnoreClasses() {
		if (strlen($this->piwikOptions['setIgnoreClasses'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setIgnoreClasses", "' . $this->piwikOptions['setIgnoreClasses'] . '"]);' . "\n";
			} else {
				return 'piwikTracker.setIgnoreClasses("' . $this->piwikOptions['setIgnoreClasses'] . '");' . "\n";
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setDownloadClasses javascript code
	 *
	 * @return	string		piwikTracker.setDownloadClasses javascript code
	 */
	function getPiwikSetDownloadClasses() {
		if (strlen($this->piwikOptions['setDownloadClasses'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setDownloadClasses", "' . $this->piwikOptions['setDownloadClasses'] . '"]);' . "\n";
			} else {
				return 'piwikTracker.setDownloadClasses("' . $this->piwikOptions['setDownloadClasses'] . '");' . "\n";
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setLinkClasses javascript code
	 *
	 * @return	string		piwikTracker.setLinkClasses javascript code
	 */
	function getPiwikSetLinkClasses() {
		if (strlen($this->piwikOptions['setLinkClasses'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setLinkClasses", "' . $this->piwikOptions['setLinkClasses'] . '"]);' . "\n";
			} else {
				return 'piwikTracker.setLinkClasses("' . $this->piwikOptions['setLinkClasses'] . '");' . "\n";
			}
		}
		return '';
	}

	/**
	 * Generates javascript code for using custom variables and
	 * initializes custom variables in the piwikTracker API for image tracking.
	 *
	 * @return string piwikTracker javascript code for initializing custom variables
	 */
	function getPiwikCustomVariables() {

		$javaScript = '';

		if (!is_array($this->piwikOptions['customVariables.'])) {
			return $javaScript;
		}

		/** @var tslib_cObj $cObject */
		$cObject = t3lib_div::makeInstance('tslib_cObj');

		$i = 1;

		foreach ($this->piwikOptions['customVariables.'] as $var) {

			$name = $cObject->stdWrap($var['name'], $var['name.']);
			$value = $cObject->stdWrap($var['value'], $var['value.']);
			$scope = $cObject->stdWrap($var['scope'], $var['scope.']);
			$scope = $scope ? $scope : 'visit';

			// The necessary javascript code.
			$arguments = trim(json_encode(array($i, $name, $value, $scope)), "[]");

			if ($this->useAsyncTrackingApi) {
				$javaScript .= '_paq.push(["setCustomVariable", ' . $arguments . ']);' . "\n";
			} else {
				$javaScript .= 'piwikTracker.setCustomVariable(' . $arguments . ');' . "\n";
			}

			// For use in the noscript area.
			$this->piwikTracker->setCustomVariable($i, $name, $value, $scope);

			$i++;
		}

		return $javaScript;
	}

	/**
	 * Gets Piwik SiteID
	 *
	 * @return	string		Piwik SiteID
	 */
	function getPiwikIDSite() {
		return $this->piwikOptions['piwik_idsite'];
	}

	/**
	 * Gets Piwik Host-URL
	 *
	 * @return	string		Piwik Host-URL
	 */
	function getPiwikHost() {
		if (t3lib_div::getIndpEnv('TYPO3_SSL')) {
			$scheme = 'https://';
		} else {
			$scheme = 'http://';
		}
		return $scheme.$this->piwikOptions['piwik_host'];
	}

	/**
	 * Creates a new instance of the piwik tracker and
	 * initializes some variables by using the TYPO3 API
	 *
	 * @return void
	 */
	protected function initializePiwikTracker() {
		$this->piwikTracker = t3lib_div::makeInstance(
			'Tx_Piwik_PiwikApi_PiwikTracker',
			$this->getPiwikIDSite(),
			$this->piwikOptions['piwik_host']
		);
		$this->piwikTracker->setUrlReferrer(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$this->piwikTracker->setUrl(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$this->piwikTracker->setIp(t3lib_div::getIndpEnv('REMOTE_ADDR'));
		$this->piwikTracker->setBrowserLanguage(t3lib_div::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
		$this->piwikTracker->setUserAgent(t3lib_div::getIndpEnv('HTTP_USER_AGENT'));
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/piwik/class.tx_piwik.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/piwik/class.tx_piwik.php"]);
}

?>
