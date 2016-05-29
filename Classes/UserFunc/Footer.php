<?php
namespace KayStrobach\Piwik\UserFunc;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Kay Strobach (typo3@kay-strobach.de)
 *  (c) 2009 Ulrich Wuensche (wuensche@drwuensche.de),
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

use KayStrobach\Piwik\PiwikApi\PiwikTracker;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;

/**
 * Based on B-Net1 Piwik plugin implementation, old piwik plugin and piwik2a
 * Provides Interface to get the new piwiktrackingcode
 *
 * Hooks for the 'piwik' extension.
 *
 * @author Ulrich Wuensche <wuensche@drwuensche.de>
 * @author Joerg Winter <winter@b-net1.de>
 * @author Kay Strobach <typo3@kay-strobach.de>
 */
class Footer {

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	/**
	 * Piwik PHP Tracking Code for generating the tracking image
	 *
	 * @var \KayStrobach\Piwik\PiwikApi\PiwikTracker
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
	public function contentPostProc_output($trackingCode, $localConfig) {
		// process the page with these options
		$conf = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_piwik.'];
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($conf, $localConfig);
		$beUserLogin = $GLOBALS['TSFE']->beUserLogin;

		if ($conf['useAsyncTrackingApi']) {
			$this->useAsyncTrackingApi = TRUE;
		}

		//check wether there is a BE User loggged in, if yes avoid to display the tracking code!
		//check wether needed parameters are set properly
		if ((!$conf['piwik_idsite']) || (!$conf['piwik_host'])) {
			//fetch the js template file, makes editing easier ;)
			$extConf = unserialize($GLOBALS['$TYPO3_CONF_VARS']['EXT']['extConf']['piwik']);
			if ($extConf['showFaultyConfigHelp']) {
				$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/notracker.html');
			} else {
				return '';
			}
		} elseif (($beUserLogin == 1) && (!intval($conf['trackBackendUsers']))) {
			$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/notracker_beuser.html');
		} else {
			//fetch the js template file, makes editing easier ;)
			if ($this->useAsyncTrackingApi) {
				$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/tracker_async.html');
			} else {
				$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/tracker.html');
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
			$trackingCode .= "\t\t" . 'piwikTracker.trackPageView();';
		}

		//replace placeholders
		//currently the function $this->getPiwikHost() is not called, because of piwikintegration?!
		$template = str_replace('###TRACKEROPTIONS###', $trackingCode, $template);
		$template = str_replace('###HOST###', $conf['piwik_host'], $template);
		$template = str_replace('###IDSITE###', $conf['piwik_idsite'], $template);
		$template = str_replace('###BEUSER###', $beUserLogin, $template);

		if (strlen($this->piwikOptions['trackGoal'])) {
			$template = str_replace('###TRACKING_IMAGE_URL###', htmlentities($this->piwikTracker->getUrlTrackGoal($this->piwikOptions['trackGoal'])), $template);
		} else {
			$template = str_replace('###TRACKING_IMAGE_URL###', htmlentities($this->piwikTracker->getUrlTrackPageView()), $template);
		}

		if (isset($this->piwikOptions['includeJavaScript']) && !(bool)$this->piwikOptions['includeJavaScript']) {
			$template = \TYPO3\CMS\Core\Html\RteHtmlParser::substituteSubpart($template, '###JAVASCRIPT_INCLUDE###', '');
		}

		return $template;
	}

	/**
	 * a stub for backwards compatibility with extending classes that might use it
	 *
	 * @return bool  always false
	 */
	function is_backend() {
		return false;
	}

	/**
	 * Generates piwikTracker.trackGoal javascript code
	 *
	 * @return string  piwikTracker.trackGoal javascript code
	 */
	protected function getPiwikTrackGoal() {
		if (strlen($this->piwikOptions['trackGoal'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["trackGoal", ' . $this->piwikOptions['trackGoal'] . ']);' . PHP_EOL;
			} else {
				return 'piwikTracker.trackGoal(' . $this->piwikOptions['trackGoal'] . ');' . PHP_EOL;
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
	protected function getPiwikActionName() {

		if ((strtoupper($this->piwikOptions['actionName']) == 'TYPO3') && !($this->piwikOptions['actionName.'])) {
			return $GLOBALS['TSFE']->cObj->data['title'];
		}

		if (strlen($this->piwikOptions['actionName'])) {
			/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObject */
			$cObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
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
				return '_paq.push(["setDocumentTitle", "' . $action . '"]);' . PHP_EOL;
			} else {
				return 'piwikTracker.setDocumentTitle("' . $action . '");' . PHP_EOL;
			}
		}

		return '';
	}

	/**
	 * Generates piwikTracker.setDownloadExtensions javascript code
	 *
	 * @return string  piwikTracker.setDownloadExtensions javascript code
	 */
	protected function getPiwikSetDownloadExtensions() {
		if (strlen($this->piwikOptions['setDownloadExtensions'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setDownloadExtensions", "' . $this->piwikOptions['setDownloadExtensions'] . '"]);' . PHP_EOL;
			} else {
				return 'piwikTracker.setDownloadExtensions( "' . $this->piwikOptions['setDownloadExtensions'] . '" );' . PHP_EOL;
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.addDownloadExtensions javascript code
	 *
	 * @return string  piwikTracker.addDownloadExtensions javascript code
	 */
	protected function getPiwikAddDownloadExtensions() {
		if (strlen($this->piwikOptions['addDownloadExtensions'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["addDownloadExtensions", "' . $this->piwikOptions['addDownloadExtensions'] . '"]);' . PHP_EOL;
			} else {
				return 'piwikTracker.addDownloadExtensions( "' . $this->piwikOptions['addDownloadExtensions'] . '" );' . PHP_EOL;
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setDomains javascript code
	 *
	 * @return string  piwikTracker.setDomains javascript code
	 */
	protected function getPiwikDomains() {
		if (strlen($this->piwikOptions['setDomains'])) {
			$hosts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->piwikOptions['setDomains']);
			for ($i = 0; $i < count($hosts); $i++) {
				$hosts[$i] = '"' . $hosts[$i] . '"';
			}
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setDomains", [' . implode(', ', $hosts) . ']]);' . PHP_EOL;
			} else {
				return 'piwikTracker.setDomains([' . implode(', ', $hosts) . ']);' . PHP_EOL;
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setLinkTrackingTimer javascript code
	 *
	 * @return string  piwikTracker.setLinkTrackingTimer javascript code
	 */
	protected function getLinkTrackingTimer() {
		if (strlen($this->piwikOptions['setLinkTrackingTimer'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setLinkTrackingTimer", ' . $this->piwikOptions['setLinkTrackingTimer'] . ']);' . PHP_EOL;
			} else {
				return 'piwikTracker.setLinkTrackingTimer(' . $this->piwikOptions['setLinkTrackingTimer'] . ');' . PHP_EOL;
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.enableLinkTracking javascript code
	 *
	 * @return string  piwikTracker.enableLinkTracking javascript code
	 */
	protected function getPiwikEnableLinkTracking() {
		if ($this->piwikOptions['enableLinkTracking'] == '0') {
			return '';
		}

		if ($this->useAsyncTrackingApi) {
			return '_paq.push(["enableLinkTracking"]);' . PHP_EOL;
		} else {
			return 'piwikTracker.enableLinkTracking();' . PHP_EOL;
		}
	}

	/**
	 * Generates piwikTracker.setIgnoreClasses javascript code
	 *
	 * @return string  piwikTracker.setIgnoreClasses javascript code
	 */
	protected function getPiwikSetIgnoreClasses() {
		if (strlen($this->piwikOptions['setIgnoreClasses'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setIgnoreClasses", "' . $this->piwikOptions['setIgnoreClasses'] . '"]);' . PHP_EOL;
			} else {
				return 'piwikTracker.setIgnoreClasses("' . $this->piwikOptions['setIgnoreClasses'] . '");' . PHP_EOL;
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setDownloadClasses javascript code
	 *
	 * @return string  piwikTracker.setDownloadClasses javascript code
	 */
	protected function getPiwikSetDownloadClasses() {
		if (strlen($this->piwikOptions['setDownloadClasses'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setDownloadClasses", "' . $this->piwikOptions['setDownloadClasses'] . '"]);' . PHP_EOL;
			} else {
				return 'piwikTracker.setDownloadClasses("' . $this->piwikOptions['setDownloadClasses'] . '");' . PHP_EOL;
			}
		}
		return '';
	}

	/**
	 * Generates piwikTracker.setLinkClasses javascript code
	 *
	 * @return string  piwikTracker.setLinkClasses javascript code
	 */
	protected function getPiwikSetLinkClasses() {
		if (strlen($this->piwikOptions['setLinkClasses'])) {
			if ($this->useAsyncTrackingApi) {
				return '_paq.push(["setLinkClasses", "' . $this->piwikOptions['setLinkClasses'] . '"]);' . PHP_EOL;
			} else {
				return 'piwikTracker.setLinkClasses("' . $this->piwikOptions['setLinkClasses'] . '");' . PHP_EOL;
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
	protected function getPiwikCustomVariables() {

		$javaScript = '';

		if (!is_array($this->piwikOptions['customVariables.'])) {
			return $javaScript;
		}

		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObject */
		$cObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

		$i = 1;

		foreach ($this->piwikOptions['customVariables.'] as $var) {

			$name = $cObject->stdWrap($var['name'], $var['name.']);
			$value = $cObject->stdWrap($var['value'], $var['value.']);
			$scope = $cObject->stdWrap($var['scope'], $var['scope.']);
			$scope = $scope ? $scope : 'visit';

			// The necessary javascript code.
			$arguments = trim(json_encode(array($i, $name, $value, $scope)), "[]");

			if ($this->useAsyncTrackingApi) {
				$javaScript .= '_paq.push(["setCustomVariable", ' . $arguments . ']);' . PHP_EOL;
			} else {
				$javaScript .= 'piwikTracker.setCustomVariable(' . $arguments . ');' . PHP_EOL;
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
	 * @return string  Piwik SiteID
	 */
	protected function getPiwikIDSite() {
		return $this->piwikOptions['piwik_idsite'];
	}

	/**
	 * Gets Piwik Host-URL
	 *
	 * @return string  Piwik Host-URL
	 */
	function getPiwikHost() {
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
			$scheme = 'https://';
		} else {
			$scheme = 'http://';
		}
		return $scheme . $this->piwikOptions['piwik_host'];
	}

	/**
	 * Creates a new instance of the piwik tracker and
	 * initializes some variables by using the TYPO3 API
	 *
	 * @return void
	 */
	protected function initializePiwikTracker() {
		$this->piwikTracker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			PiwikTracker::class,
			$this->getPiwikIDSite(),
			$this->piwikOptions['piwik_host']
		);
		$this->piwikTracker->setUrlReferrer(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$this->piwikTracker->setUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
		$this->piwikTracker->setIp(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'));
		$this->piwikTracker->setBrowserLanguage(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
		$this->piwikTracker->setUserAgent(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
	}

}
