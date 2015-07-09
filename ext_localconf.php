<?php
if (!defined("TYPO3_MODE")) die ("Access denied.");

if (TYPO3_MODE == 'FE') {
	require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('piwik') . 'Classes/UserFunc/Footer.php');
}
