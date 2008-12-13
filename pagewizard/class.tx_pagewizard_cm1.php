<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007  <>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
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
 * Addition of an item to the clickmenu
 *
 * @author	 <>
 * @package	TYPO3
 * @subpackage	tx_pagewizard
 */
class tx_pagewizard_cm1 {
	function main(&$backRef,$menuItems,$table,$uid)	{
		global $BE_USER,$TCA,$LANG;
	
		$localItems = Array();
		if (!$backRef->cmLevel)	{
			if ($backRef->editOK)	{
				
					// Returns directly, because the clicked item was not from the pages table 
				if ($table!="pages")	return $menuItems;
				
					// Adds the regular item:
				$LL = $this->includeLL();
				
					// Repeat this (below) for as many items you want to add!
					// Remember to add entries in the localconf.php file for additional titles.
				$url = t3lib_extMgm::extRelPath("pagewizard")."cm1/index.php?id=".$uid;
				$localItems[] = $backRef->linkItem(
					$GLOBALS["LANG"]->getLLL("cm1_title",$LL),
					$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath("pagewizard").'cm1/cm_icon.gif" width="16" height="16" border="0" align="top" />'),
					$backRef->urlRefForCM($url),
					1	// Disables the item in the top-bar. Set this to zero if you with the item to appear in the top bar!
				);
				
				
				
				// Traverse items array
				while(list($k) = each($menuItems)) {
					
					// Increase counter
					$c++;
					
					// Find "new" item
					if (!strcmp($k,"new")) {
						
						// Exit
						break;
					}
				}
				
				//if no new item insert as third item
				if($c == count($menuItems)) {
					$c = 2;
				}
				
				//if item is disabled return
				if (in_array('pagewizard',$backRef->disabledItems) || !$TCA[$table]) return $menuItems;
				// Insert menu
				array_splice($menuItems,$c,0,$localItems);
			}
		}
		return $menuItems;
	}
	
	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	[type]		...
	 */
	function includeLL()	{
		global $LANG;
	
		$LOCAL_LANG = $LANG->includeLLFile('EXT:pagewizard/locallang.xml',FALSE);
		return $LOCAL_LANG;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagewizard/class.tx_pagewizard_cm1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagewizard/class.tx_pagewizard_cm1.php']);
}

?>