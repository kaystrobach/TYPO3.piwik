<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");
if(TYPO3_MODE=='FE') {
	require_once(t3lib_extMgm::extPath('piwik').'Classes/UserFunc/Footer.php');
	#$t = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['piwik']);
	#if($t['useContentPostProcAll']) {
		/**	
		 * the following allows to put the code in the cache - thanks to kelsaka for the hint:
		 * http://forge.typo3.org/issues/26318
		 */
		#$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'tx_piwik->contentPostProc_output';
	#} else {
		#$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'tx_piwik->contentPostProc_output';
	#}
} 