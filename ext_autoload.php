<?php
/*
 * Register necessary class names with autoloader
 *
 */
$classesPath = t3lib_extMgm::extPath('piwik', 'Classes/');
return array(
	'tx_piwik_piwikapi_piwiktracker'	=> $classesPath . 'PiwikApi/PiwikTracker.php',
);
?>