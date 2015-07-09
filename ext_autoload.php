<?php
/*
 * Register necessary class names with autoloader
 *
 */
$classesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik', 'Classes/');
return array(
	'tx_piwik_piwikapi_piwiktracker'	=> $classesPath . 'PiwikApi/PiwikTracker.php',
);
?>
