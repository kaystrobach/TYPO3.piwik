<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

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

use KayStrobach\Piwik\PiwikApi\MatomoTracker;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
	 * @var \KayStrobach\Piwik\PiwikApi\MatomoTracker
	 */
	protected $matomoTracker;

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

		$context = GeneralUtility::makeInstance(Context::class);
		$backendUserIsLoggedIn = $context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);

		if ($conf['useAsyncTrackingApi']) {
			$this->useAsyncTrackingApi = TRUE;
		}

		//check wether there is a BE User loggged in, if yes avoid to display the tracking code!
		//check wether needed parameters are set properly
		if ((!$conf['piwik_idsite']) || (!$conf['piwik_host'])) {
			//fetch the js template file, makes editing easier ;)
			$extConf = unserialize($GLOBALS['$TYPO3_CONF_VARS']['EXT']['extConf']['piwik']);
			if ($extConf['showFaultyConfigHelp']) {
				$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/notracker.html');
			} else {
				return '';
			}
		} elseif ($backendUserIsLoggedIn && !intval($conf['trackBackendUsers'])) {
			$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/notracker_beuser.html');
		} else {
			//fetch the js template file, makes editing easier ;)
			if ($this->useAsyncTrackingApi) {
				$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/tracker_async.html');
			} else {
				$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Resources/Private/Templates/Piwik/tracker.html');
			}
		}

		//make options accessable in the whole class
		$this->piwikOptions = $conf;

		$this->initializeMatomoTracker();

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
		$trackingCode .= $this->getPiwikCustomDimensions();
		$trackingCode .= $this->getPiwiksetUserId();
		$trackingCode .= $this->getAdditionalTrackers();

		if (!$this->useAsyncTrackingApi) {
			$trackingCode .= "\t\t" . 'piwikTracker.trackPageView();';
		}

		//replace placeholders
		//currently the function $this->getPiwikHost() is not called, because of piwikintegration?!
		$template = str_replace('###TRACKEROPTIONS###', $trackingCode, $template);
		$template = str_replace('###HOST###', $conf['piwik_host'], $template);
		$template = str_replace('###IDSITE###', $conf['piwik_idsite'], $template);
		$template = str_replace('###BEUSER###', ($backendUserIsLoggedIn ? 'yes' : 'no'), $template);

		$template = str_replace('###TRACKING_IMAGES###', $this->buildTrackingImages(), $template);

		if (isset($this->piwikOptions['includeJavaScript']) && !(bool)$this->piwikOptions['includeJavaScript']) {
			$templateService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
			$templateService->substituteSubpart($template, '###JAVASCRIPT_INCLUDE###', '');
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
	 * @return string
	 */
	protected function buildTrackingImages()
	{
		$trackingImages = $this->buildTrackingImageTag($this->matomoTracker);

		if (empty($this->piwikOptions['additionalTrackers.']) || !is_array($this->piwikOptions['additionalTrackers.'])) {
			return $trackingImages;
		}

		foreach ($this->piwikOptions['additionalTrackers.'] as $trackerConfig) {
			$addionalTrackerUrl = rtrim($trackerConfig['piwik_host'], '/');
			$addionalTrackerSiteId = (int)$trackerConfig['piwik_idsite'];
			MatomoTracker::$URL = $addionalTrackerUrl;
			$this->matomoTracker->setIdSite($addionalTrackerSiteId);
			$trackingImages .= $this->buildTrackingImageTag($this->matomoTracker);
		}

		return $trackingImages;
	}

	/**
	 * @param MatomoTracker $matomoTracker
	 * @return string
	 */
	protected function buildTrackingImageTag(MatomoTracker $matomoTracker)
	{
		if (strlen($this->piwikOptions['trackGoal'])) {
			$imageSrc = $matomoTracker->getUrlTrackGoal($this->piwikOptions['trackGoal']);
		} else {
			$currentPageTitle = $this->getCurrentPageTitle();
			$imageSrc = $matomoTracker->getUrlTrackPageView($currentPageTitle);
		}

		return sprintf('<img src="%s" style="border:0" alt=""/>', htmlspecialchars($imageSrc)) . PHP_EOL;
	}

	protected function getAdditionalTrackers()
	{
		$additionalTrackerCode = '';
		if (empty($this->piwikOptions['additionalTrackers.']) || !is_array($this->piwikOptions['additionalTrackers.'])) {
			return $additionalTrackerCode;
		}

		foreach ($this->piwikOptions['additionalTrackers.'] as $trackerConfig) {
			$addionalTrackerUrl = $trackerConfig['piwik_host'] . 'matomo.php';
			$addionalTrackerSiteId = (int)$trackerConfig['piwik_idsite'];
			$pushParameters = [
				'addTracker',
				$addionalTrackerUrl,
				$addionalTrackerSiteId,
			];
			$additionalTrackerCode .= '_paq.push(' . json_encode($pushParameters, JSON_UNESCAPED_SLASHES) . ');' . PHP_EOL;
		}

		return $additionalTrackerCode;
	}

	/**
	 * Returns the page title set in the TSFE page renderer.
	 *
	 * @return string
	 */
	protected function getCurrentPageTitle() {
		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		return $pageRenderer->getTitle();
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
			$this->matomoTracker->setCustomVariable($i, $name, $value, $scope);

			$i++;
		}

		return $javaScript;
	}

	/**
	 * Generates javascript code for using custom dimensions and
	 * initializes custom dimensions in the piwikTracker API for image tracking.
	 * See http://piwik.org/docs/custom-dimensions/
	 *
	 * @return string piwikTracker javascript code for initializing custom dimensions
	 */
	protected function getPiwikCustomDimensions() {
		$javaScript = '';

		if (!is_array($this->piwikOptions['customDimensions.'])) {
			return $javaScript;
		}

		foreach ($this->piwikOptions['customDimensions.'] as $dimensionConfig) {
			$dimId = (int)$dimensionConfig['dimId'];
			if ($dimId < 1) {
				continue;
			}

			$dimVal = trim($this->stdWrapConfigValue($dimensionConfig, 'dimVal'));
			if ($dimVal === '') {
				continue;
			}

			$arguments = trim(json_encode(array($dimId, $dimVal)), "[]");

			if ($this->useAsyncTrackingApi) {
				$javaScript .= '_paq.push(["setCustomDimension", ' . $arguments . ']);' . PHP_EOL;
			} else {
				$javaScript .= 'piwikTracker.setCustomDimension(' . $arguments . ');' . PHP_EOL;
			}

			// For use in the noscript area.
			$this->matomoTracker->setCustomTrackingParameter('dimension' . $dimId, $dimVal);
		}

		return $javaScript;
	}

	 /**
	 * Generates javascript code for using user id's and
	 * initializes user id's in the piwikTracker API for image tracking.
	 * See https://piwik.org/docs/user-id/
	 *
	 * @return string piwikTracker javascript code for initializing user id's
	 */
	protected function getPiwiksetUserId() {
		$javaScript = '';

		$userId = trim($this->stdWrapConfigValue($this->piwikOptions, 'setUserId'));
		if ($userId === '') {
			return $javaScript;
		}

		if ($this->useAsyncTrackingApi) {
			$javaScript .= '_paq.push(["setUserId", ' . json_encode($userId) . ']);' . PHP_EOL;
		} else {
			$javaScript .= 'piwikTracker.setUserId(' . json_encode($userId) . ');' . PHP_EOL;
		}

		// For use in the noscript area.
		$this->matomoTracker->setUserId($userId);

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
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getTypoScriptFrontendController() {
		return $GLOBALS['TSFE'];
	}

	/**
	 * Creates a new instance of the piwik tracker and
	 * initializes some variables by using the TYPO3 API
	 *
	 * @return void
	 */
	protected function initializeMatomoTracker() {
		$this->matomoTracker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			MatomoTracker::class,
			$this->getPiwikIDSite(),
			rtrim($this->piwikOptions['piwik_host'], '/')
		);
		$this->matomoTracker->setUrlReferrer(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$this->matomoTracker->setUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
		$this->matomoTracker->setIp(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'));
		$this->matomoTracker->setBrowserLanguage(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
		$this->matomoTracker->setUserAgent(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
	}

	/**
	 * Checks if a stdWrap configuration exists for the given config key
	 * in the given TypoScript configuration array.
	 *
	 * @param array $config
	 * @param string $configKey
	 * @return string
	 */
	protected function stdWrapConfigValue(array $config, $configKey) {
		$configValue = isset($config[$configKey]) ? $config[$configKey] : '';
		$stdWrapKey = $configKey . '.';
		if (!empty($config[$stdWrapKey]) && is_array($config[$stdWrapKey])) {
			$configValue = $this->cObj->stdWrap($configValue, $config[$stdWrapKey]);
		}
		return $configValue;
	}
}
