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


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:pagewizard/cm1/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
	// ....(But no access check here...)
	// DEFAULT initialization of a module [END]
// ***************************
// Including classes
// ***************************
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once('class.positionmap.php');
require_once(PATH_t3lib.'class.t3lib_pagetree.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_transferdata.php');


/**
 * Extension for the tree class that generates the tree of pages in the page-wizard mode
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localPageTree extends t3lib_pageTree {

	/**
	 * Inserting uid-information in title-text for an icon
	 *
	 * @param	string		Icon image
	 * @param	array		Item row
	 * @return	string		Wrapping icon image.
	 */
	function wrapIcon($icon,$row)	{
		return $this->addTagAttributes($icon,' title="id='.htmlspecialchars($row['uid']).'"');
	}

	/**
	 * Determines whether to expand a branch or not.
	 * Here the branch is expanded if the current id matches the global id for the listing/new
	 *
	 * @param	integer		The ID (page id) of the element
	 * @return	boolean		Returns true if the IDs matches
	 */
	function expandNext($id)	{
		return $id==$GLOBALS['SOBE']->id ? 1 : 0;
	}
}

/**
 * pagewizard module cm1
 *
 * @author	 Reinhard FÃ¼hricht <rf@typoheads.at>
 * @package	TYPO3
 * @subpackage	tx_pagewizard
 */
class tx_pagewizard_cm1 extends t3lib_SCbase {
				/**
				 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
				 *
				 * @return	void
				 */
				function menuConfig()	{
					global $LANG;
					$this->MOD_MENU = Array (
						'function' => array (
							'1' => $LANG->getLL('function1'),
							#'2' => $LANG->getLL('function2'),
							#'3' => $LANG->getLL('function3'),
						)
					);
					parent::menuConfig();
				}

				/**
				 * Main function of the module. Write the content to $this->content
				 *
				 * @return	void
				 */
				function main()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
					
					$this->colors = array(
						"#dddddd",
						"#ebebeb",
						"#fafafa"
						
					);
					
					$conf = t3lib_BEfunc::getModTSconfig($this->id, 'mod.tx_pagewizard');
					$this->conf = $conf['properties'];
					
						// Draw the header.
					$this->doc = t3lib_div::makeInstance('mediumDoc');
					$this->doc->backPath = $BACK_PATH;
					#$this->doc->form = '<form action="" method="post">';

						// JavaScript
					#print $this->doc->JScode;
					$this->doc->JScode .= '
						<script language="javascript" type="text/javascript">
							script_ended = 0;
							function jumpToUrl(URL)	{
								document.location = URL;
							}
						</script>
					';

					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
					$access = is_array($this->pageinfo) ? 1 : 0;
					if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
						if ($BE_USER->user['admin'] && !$this->id)	{
							$this->pageinfo = array(
									'title' => '[root-level]',
									'uid'   => 0,
									'pid'   => 0
							);
						}

						$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'
								.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'], 50);
						
						$this->content.=$this->doc->header($LANG->getLL('title'));
						#$this->content.=$this->doc->spacer(5);
						#$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
						#$this->content.=$this->doc->divider(5);


						// Render content:
						$this->moduleContent();
						#print $this->doc->JScode;
						$this->content = $this->doc->startPage($LANG->getLL('title')).$this->content;

						// ShortCut
						#if ($BE_USER->mayMakeShortcut())	{
					#		$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
				#		}
					}
					$this->content.=$this->doc->spacer(10);
				}

				/**
				 * Print the content
				 *
				 * @return	void
				 */
				function printContent()	{

					$this->content.=$this->doc->endPage();
					echo $this->content;
				}

				/**
				 * Create output content
				 *
				 * @return	void
				 */
				function moduleContent()	{
					global $BE_USER,$TCA;
					
					$this->fe_mode = t3lib_div::_GP('pagewizard_fe_mode');
					
					if(!$this->id) {
						$this->id= t3lib_div::_GP('id');
					}
					switch((string) $this->MOD_SETTINGS['function'])	{
						case 1:
							$this->cmd=t3lib_div::_GP('cmd');
							if($this->cmd == "") {
								$content = $this->doStep1();
							} else {
								switch($this->cmd) {
									case "crPage":
										if($this->fe_mode) {
											$content = $this->doStep3();
										} else {
											$content = $this->doStep2();
										}
									break;
									case "setTitle":
										$content = $this->doStep3();
									break;
									case "createPage":
										$content = $this->doStep4();
									break;
								}
							}
							$this->content .= $this->doc->section('',$content,0,1);
						break;
					}
				}
				
				/**
				 * Show the pagetree wizard for the user to choose a position for the new page
				 *
				 * @return	string		output
				 */
				function doStep1() {
					global $LANG,$TCA;
					#print_r($TCA['pages']);
					$content = "";
					
					$content .= $this->printHeaderStuff(1,'selectPosition');

					//set needed params
					$conf = t3lib_BEfunc::getModTSconfig($this->id, 'mod.tx_pagewizard');
					$container_id = $conf['properties']['presetPagesPid'];
					
					//initialize positionMap for printing the wizard for choosing position of the new page
					$posMap = t3lib_div::makeInstance('positionMap');
					$posMap->backPath = $this->doc->backPath;
					if($this->fe_mode) {
						$posMap->fe_mode = 1;
						
						#$posMap->preset_id = t3lib_div::_GP('pagewizard_preset_id');
					}


					if($container_id || $this->fe_mode) {
						$content.= $posMap->positionTree($this->id,$this->pageinfo,$this->perms_clause,$this->R_URI);

							// Add CSH:
						$content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'new_pages', $GLOBALS['BACK_PATH'],'<br/>');

					//no storage id specified in page ts config
					} else {
						$content .= '
								<div style="margin-bottom:5px;padding-left:10px;">
								'.htmlspecialchars($LANG->getLL('no_storage')).'
								</div>
							';
					}

					return $content;
				}
				
				function getAccessInfo($pid) {
					global $BE_USER;
					$hasAccess = 1;
					$calcPRec=t3lib_BEfunc::getRecord('pages',abs($pid));
					if (is_array($calcPRec))	{
						$CALC_PERMS = $BE_USER->calcPerms($calcPRec);	// Permissions for the parent page
						$hasAccess = $CALC_PERMS&8 ? 1 : 0;
					}
					return $hasAccess;
				}
				
				/**
				 * Read the preset pages from the storage pid specified in page ts config
				 *
				 * @return	string		output
				 */
				function doStep2() {
					global $LANG,$TCA;
					#print_r($TCA['pages']['columns']['doktype']['config']['items']);
					$content = $this->printHeaderStuff(2,'select_preset');

					//set needed params
					$this->id = t3lib_div::_GP('id');
					$conf = t3lib_BEfunc::getModTSconfig($this->id, 'mod.tx_pagewizard');
					$container_id = $conf['properties']['presetPagesPid'];
					if($container_id) {
						//read preset pages from specified page
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,doktype','pages','deleted=0 AND hidden=0 AND pid='.$container_id);

						//no page found
						if(!$res) {
							$content .= '
								<div style="margin-bottom:5px;padding-left:10px;">
								'.htmlspecialchars($LANG->getLL('no_preset')).'
								</div>
							';

						//pages found
						} else {
							
							//print links with according page icon
							$backgroundcolor = $this->colors[0];
							$content .= '<div style="background-color:'.$backgroundcolor.';">';
							while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
								
								$content .= $this->readChildPages($row,0);
								
								
							}
							$content .= '</div>';
							
						}

					//no storage id for preset pages found
					} else {
						$content .= '
								<div style="margin-bottom:5px;padding-left:10px;">
								'.htmlspecialchars($LANG->getLL('no_storage')).'
								</div>

							';
					}

					return $content;
				}
				
				function readChildPages($row,$level) {
					global $LANG;
					if($GLOBALS['PAGES_TYPES'][$row['doktype']]['icon']) {
						$icon = $GLOBALS['PAGES_TYPES'][$row['doktype']]['icon'];
					} else {
						$icon = $GLOBALS['PAGES_TYPES']['default']['icon'];
					}
					if(!strstr($GLOBALS['PAGES_TYPES'][$row['doktype']]['icon'],"/") || !strstr($GLOBALS['PAGES_TYPES'][$row['doktype']]['icon'],"../")) {
						$icon = 'gfx/i/'.$icon;
					} else {
						$icon = '../'.$icon;
					}
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,doktype','pages','deleted=0 AND hidden=0 AND pid='.$row['uid']);
					$records = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
				
					$hasSub = "1";
					if($records == 0) {
						$hasSub = "0";
					}
					
					$img = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,$icon,'').' border="0" />';
					$padding = 10+15*$level;
					$backgroundcolor = $this->colors[$level+1];
					$content .= '
						
						<div style="margin-bottom:2px;padding-top:3px;padding-left:'.$padding.'px;"><table cellspacing="0" cellpadding="0" border="0" style="margin:0"><tr><td>'.$img.'
							</td><td valign="middle">
							<a href="index.php?id='.$this->id.'&pos_pid='.t3lib_div::_GP('positionPid').'&cmd=setTitle&hasSub='.$hasSub.'&preset_id='.$row['uid'].'">'.$row['title'].'</a>
						</td></tr></table></div>
						
					
					';
					if($records <= 0 || !$res) {
						return $content;
					} else {
						$level++;
						$content .= '<div style="background-color:'.$backgroundcolor.';">';
						while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$content .= $this->readChildPages($row,$level);
						}
						$content .= "</div>";
					}
					return $content;
				}
				
				/**
				 * Display form for user to enter a new page title
				 *
				 * @return	string		output
				 */
				function doStep3() {
					global $LANG,$TCA,$BACK_PATH,$BE_USER;
					#print "call";
					$conf = t3lib_BEfunc::getModTSconfig($this->id, 'mod.tx_pagewizard');
					$conf = $conf['properties'];
					$presetID = t3lib_div::_GP('preset_id');
					if($this->fe_mode) {
						$presetID = $conf['pagewizardPresetId'];
					}
					#print $presetID;
					$content = $this->printHeaderStuff(3,'new_title');

					//set needed params
					$this->id = t3lib_div::_GP('id');
					
					$hasSub = t3lib_div::_GP('hasSub');

					
					
					//print form
					$content .= '
						<div style="padding-left:10px;">
						<form action="index.php" method="POST" name="editform">
							
							<input type="hidden" name="cmd" value="createPage" />
							<input type="hidden" name="id" value="'.$this->id.'" />
							<input type="hidden" name="preset_id" value="'.$presetID.'" />
							<input type="hidden" name="pos_pid" value="'.t3lib_div::_GP('pos_pid').'" />
					';
					
					if($this->fe_mode) {
						$content .= '<input type="hidden" name="pagewizard_fe_mode" value="1" />';
					}
					
					
					$content .= '
							<label style="display:block;font-weight:bold" for="pageOnly">'.htmlspecialchars($LANG->getLL('pageOnly')).'</label><br />
							';
					if ($hasSub) {
						$content .= '	
							<input type="radio" name="pageOnly" id="pageOnly" value="2"/>'.$LANG->getLL('rootpageOnly').'<br />
							<input type="radio" name="pageOnly" id="pageOnly" value="1"/>'.$LANG->getLL('allPages').'<br />
							
						';
					}
					else {
						$content .= '	
							<input type="radio" name="pageOnly" id="pageOnly" value="1"/>'.$LANG->getLL('all').'<br />
							
						';
					}
					$content .= '
							
							<input type="radio" name="pageOnly" id="pageOnly" value="0" checked="checked"/>'.$LANG->getLL('none').'<br />
							<br />
							
					';
					if ($hasSub) {
						$content .= '
							<label style="font-weight:bold" for="includeSub">'.htmlspecialchars($LANG->getLL('subpages')).'</label>
							<input type="checkbox" name="includeSub" id="includeSub" value="1"/><br /><br />
						';
					}
					
					#print $presetID;
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','pages','uid='.$presetID);
					if($res && $this->getAccessInfo(t3lib_div::_GP('pos_pid'))) {
					#if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
						#print "aldj";
						$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
						
						#print "call2";
						if($this->conf['doktypes.'][$row['doktype']."."]['allowedFields'] != "") {
							$allowed = t3lib_div::trimExplode(',',$this->conf['doktypes.'][$row['doktype']."."]['allowedFields']);
						}
						if($this->conf['doktypes.'][$row['doktype']."."]['disallowedFields'] != "") {
							$disallowed = t3lib_div::trimExplode(',',$this->conf['doktypes.'][$row['doktype']."."]['disallowedFields']);
						}
						if($this->conf['doktypes.']["all."]['allowedFields']) {
							$global_allowed = t3lib_div::trimExplode(',',$this->conf['doktypes.']["all."]['allowedFields']);
						}
						if($this->conf['doktypes.']["all."]['disallowedFields']) {
							$global_disallowed = t3lib_div::trimExplode(',',$this->conf['doktypes.']["all."]['disallowedFields']);
						}
						#print_r($global_disallowed);
						if($this->conf['doktypes.']["all."]['allowedFields'] && !empty($global_allowed)) {
							if($allowed) {
						  	$allowed = array_merge($allowed,$global_allowed);
						  } else {
						  	$allowed = $global_allowed;
						  }
						  
						}
						if($this->conf['doktypes.']["all."]['disallowedFields'] && !empty($global_disallowed)) {
							if($disallowed) {
						  	$disallowed = array_merge($disallowed,$global_disallowed);
						  } else {
						  	$disallowed = $global_disallowed;
						  }
						}
						#print_r($disallowed);
						$allowedFields = array();
						if($this->conf['doktypes.'][$row['doktype']."."]['disallowedFields'] || $this->conf['doktypes.']["all."]['disallowedFields']) {
							$disallowedFields = array();
							foreach($disallowed as $field) {
								if(stristr($TCA['pages']['types'][$row['doktype']]['showitem'],$field)) {
									$disallowedFields[] = $field;
								}
							}
							$availableFields = array_keys($row);
							
							foreach($availableFields as $available) {
								if(!in_array($available,$disallowedFields)) {
									if(stristr($TCA['pages']['types'][$row['doktype']]['showitem'],$available)) {
										$allowedFields[] = $available;
									}
								}
							}
							$allowedFields = implode(',',$allowedFields);
						} elseif ($this->conf['doktypes.'][$row['doktype']."."]['allowedFields'] || $this->conf['doktypes.']["all."]['allowedFields']) {
							
							foreach($allowed as $field) {
								if(stristr($TCA['pages']['types'][$row['doktype']]['showitem'],$field)) {
									$allowedFields[] = $field;
								}
							}
							$allowedFields = implode(',',$allowedFields);
						}
						
						$table = 'pages';
						$content .= '<br /><h3>'.$LANG->getLL('dynamic_fields').'</h3><br />';
						#print_r($this);
						#require_once('../class.t3lib_tceforms.php');
							// Initialize TCEforms (rendering the forms)
						$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
						$this->tceforms->initDefaultBEMode();
						$this->tceforms->totalWrap = '
						<table border="0" cellspacing="0" cellpadding="0" width="'.($this->docLarge ? 440+150 : 440).'" class="typo3-TCEforms">'.
							'|'.
							'<tr>
								<td>&nbsp;</td>
								<td><img src="clear.gif" width="'.($this->docLarge ? 440+150 : 440).'" height="1" alt="" /></td>
							</tr>
						</table>';
						#$this->tceforms->doSaveFieldName = 'doSave';
						$this->tceforms->localizationMode = '';
						#$this->tceforms->returnUrl = t3lib_div::linkThisScript(array('CMD' => NULL, 'data' => NULL, 'id' => -$this->dmailRec['uid'], 'createMailFrom_UID' => NULL));
						$this->tceforms->palettesCollapsed = 0;
						$this->tceforms->disableRTE = 0;
						$this->tceforms->backPath = $this->doc->backPath;
						
						$this->tceforms->enableClickMenu = false;
						$this->tceforms->enableTabMenu = true;
						$this->tceforms->globalShowHelp = true;
						$this->tceforms->edit_showFieldHelp= $BE_USER->uc['edit_showFieldHelp'];
						$this->tceforms->hiddenFieldList = '';
						$this->tceforms->registerDefaultLanguageData($table,$row);
						#$trData = t3lib_div::makeInstance('t3lib_transferData');
						#$trData->addRawData = TRUE;
						#$trData->defVals = $this->defVals;
						#$trData->lockRecords=1;
						#$trData->disableRTE = $this->MOD_SETTINGS['disableRTE'];
						#$trData->prevPageID = $prevPageID;
						#$trData->fetchRecord($table,t3lib_div::_GP('preset_id'),"");
						#reset($trData->regTableItems_data);
						#$row = current($trData->regTableItems_data);
						
						$fields = implode(',',$row);
						$form = "";
					
						if (count($allowedFields)>0)	{
							$form.= $this->tceforms->getListedFields($table,$row,$allowedFields);
						} else {
							$form.= $this->tceforms->getMainFields($table,$row);
						}
						$form = $this->tceforms->wrapTotal($form,$row,$table);
						
						$form .= $this->tceforms->printNeededJSFunctions_top();
						$form .= $this->tceforms->printNeededJSFunctions();
						$form = $this->getDynTabMenuJScode().$form;
					
							// Display "is-locked" message:
						if ($lockInfo = t3lib_BEfunc::isRecordLocked($table,$row['uid']))	{
							$lockIcon = '

								<!--
								 	Warning box:
								-->
								<table border="0" cellpadding="0" cellspacing="0" class="warningbox">
									<tr>
										<td><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/recordlock_warning3.gif','width="17" height="12"').' alt="" /></td>
										<td>'.htmlspecialchars($lockInfo['msg']).'</td>
									</tr>
								</table>
							';
						} else $lockIcon = '';

							// Combine it all:
						$form = $lockIcon.$form;
						$content .= $form;
							
						
					}
					$content .= '
							<input type="submit" value="'.htmlspecialchars($LANG->getLL('submit')).'" />
						</form>
						</div>
					';
					
					
					return $content;

				}
				
				
				/**
				 * Returns dynamic tab menu header JS code.
				 *
				 * @return	string		JavaScript section for the HTML header.
				 */
				function getDynTabMenuJScode()	{
					return '
						<script type="text/javascript">
						/*<![CDATA[*/
							var DTM_array = new Array();
							var DTM_origClass = new String();
			
								// if tabs are used in a popup window the array might not exists
							if(!top.DTM_currentTabs) {
								top.DTM_currentTabs = new Array();
							}
			
							function DTM_activate(idBase,index,doToogle)	{	//
									// Hiding all:
								if (DTM_array[idBase])	{
									for(cnt = 0; cnt < DTM_array[idBase].length ; cnt++)	{
										if (DTM_array[idBase][cnt] != idBase+"-"+index)	{
											document.getElementById(DTM_array[idBase][cnt]+"-DIV").style.display = "none";
											document.getElementById(DTM_array[idBase][cnt]+"-MENU").attributes.getNamedItem("class").nodeValue = "tab";
										}
									}
								}
			
									// Showing one:
								if (document.getElementById(idBase+"-"+index+"-DIV"))	{
									if (doToogle && document.getElementById(idBase+"-"+index+"-DIV").style.display == "block")	{
										document.getElementById(idBase+"-"+index+"-DIV").style.display = "none";
										if(DTM_origClass=="") {
											document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tab";
										} else {
											DTM_origClass = "tab";
										}
										top.DTM_currentTabs[idBase] = -1;
									} else {
										document.getElementById(idBase+"-"+index+"-DIV").style.display = "block";
										if(DTM_origClass=="") {
											document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tabact";
										} else {
											DTM_origClass = "tabact";
										}
										top.DTM_currentTabs[idBase] = index;
									}
								}
							}
							function DTM_toggle(idBase,index,isInit)	{	//
									// Showing one:
								if (document.getElementById(idBase+"-"+index+"-DIV"))	{
									if (document.getElementById(idBase+"-"+index+"-DIV").style.display == "block")	{
										document.getElementById(idBase+"-"+index+"-DIV").style.display = "none";
										if(isInit) {
											document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tab";
										} else {
											DTM_origClass = "tab";
										}
										top.DTM_currentTabs[idBase+"-"+index] = 0;
									} else {
										document.getElementById(idBase+"-"+index+"-DIV").style.display = "block";
										if(isInit) {
											document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tabact";
										} else {
											DTM_origClass = "tabact";
										}
										top.DTM_currentTabs[idBase+"-"+index] = 1;
									}
								}
							}
			
							function DTM_mouseOver(obj) {	//
									DTM_origClass = obj.attributes.getNamedItem(\'class\').nodeValue;
									obj.attributes.getNamedItem(\'class\').nodeValue += "_over";
							}
			
							function DTM_mouseOut(obj) {	//
									obj.attributes.getNamedItem(\'class\').nodeValue = DTM_origClass;
									DTM_origClass = "";
							}
			
			
						/*]]>*/
						</script>
					';
				}
				
				function getPagetypeDropdown() {
					global $TCA,$LANG;
					$dropdown = '<select name="pagetype" id="pagetype">
									<option value="">'.$LANG->getLL('leave').'</option>
									<option value="hide">'.$LANG->getLL('hidden').'</option>
					';
					//read page types and icons from TCA and PAGES_TYPES
					foreach($TCA['pages']['columns']['doktype']['config']['items'] as $idx=>$params) {
						if($params[1] != '--div--') {
							$dropdown .= '<option value="'.$params[1].'">'.$LANG->sL($params[0]).'</option>';
						}
					}
					
					
					$dropdown .= '
								</select>';
					return $dropdown;
				}

				/**
				 * Copy the preset page to a new position and update page title
				 *
				 * @return	string		ouput
				 */
				function doStep4() {
					global $BACK_PATH,$LANG;

					$content = $this->printHeaderStuff(4,'success');
					#print_r($GLOBALS['BE_USER']);
					//set needed params
					$this->id = t3lib_div::_GP('id');
					$presetID = t3lib_div::_GP('preset_id');
					$title = t3lib_div::_GP('title');
					$posPid = t3lib_div::_GP('pos_pid');
					$this->pageOnly = t3lib_div::_GP('pageOnly');
					$includeSub = t3lib_div::_GP('includeSub');
					$this->BE_USER = $GLOBALS['BE_USER'];
					$this->admin = $this->BE_USER->user['admin'];
					#if(!$this->pageOnly) {
					#	$this->pageOnly = 1;
					#}
					//initialize tcemain for page copy
					$this->tcemain = t3lib_div::makeInstance('t3lib_TCEmain');
					$this->tcemain->start("","",$GLOBALS['BE_USER']);
					$this->transferData = t3lib_div::makeInstance('t3lib_transferData');
					//set allowed tables to copy
					$copyTablesArray = $this->admin ? $this->tcemain->compileAdminTables() : explode(',',$this->BE_USER->groupData['tables_modify']);	// These are the tables, the user may modify
					#print_r($copyTablesArray);
					$copyTablesArray = array_unique($copyTablesArray);
					$updateFields = array();
					
					//copy no content from rootpage
					if($this->pageOnly == 2) {
						$rootCopyTablesArray = array('pages');
						
						if(t3lib_extmgm::isLoaded('templavoila')) {
							$updateFields['tx_templavoila_flex'] = "";
							#$updateFields['t3_origuid'] = 0;
						}
						
					//copy no content
					} elseif ($this->pageOnly == 1) {
						$rootCopyTablesArray = array('pages');
						# by peter: only pages should be copied - no content elements
						$copyTablesArray = array('pages');

						if(t3lib_extmgm::isLoaded('templavoila')) {
							$updateFields['tx_templavoila_flex'] = "";
							#$updateFields['t3_origuid'] = 0;
						}
					} else {
						$rootCopyTablesArray = $copyTablesArray;
						#print t3lib_extmgm::isLoaded('templavoila')?"ja":"nein";
						#$updateFields['tx_templavoila_flex'] = "";
					}
					
					/*if(t3lib_extmgm::isLoaded('templavoila')) {
						$copyTablesArray = array("pages");
					}*/
					
					if(!in_array('pages',$copyTablesArray)) {
						$copyTablesArray[] = 'pages';
					}
					$this->newIds = array();
					if($includeSub) {
						$this->tcemain->copyTree = 1;
						//copy the preset page to new position in pagetree
						
						$ins_id = $this->copyPages($presetID,$posPid,$rootCopyTablesArray,$copyTablesArray);
						$this->newIds[$presetID] = $ins_id;
					} else {
					
						//copy the preset page to new position in pagetree
						
						$ins_id = $this->tcemain->copySpecificPage($presetID,$posPid,$copyTablesArray);
						$this->newIds[$presetID] = $ins_id;
					}
					
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_templavoila_flex','pages','uid='.$ins_id);
					if($res) {
						$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					}
					$pagetype = t3lib_div::_GP('pagetype');
					if($pagetype) {
						if($pagetype == "hide") {
							$updateFields['hidden'] = "1";
						} else {
							$updateFields['doktype'] = $pagetype;
						}
					}
					
					$ids = $this->newIds;
				/*	<field index="field_content_c1">
                    <value index="vDEF">1721,1708</value>
                </field>*/
					# content elements should not be copied
					
					
					if(t3lib_extmgm::isLoaded('templavoila') && $this->pageOnly != 1) { # && $this->pageOnly != '0') {
						
						//for every copied page
						foreach($ids as $origID=>$newID) {
							$updateFields = array();
							
							//select original templavoila flexform
							$res_orig = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_templavoila_flex','pages','uid='.$origID);
							if($res_orig) {
								$row_orig = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_orig);
							}
							
							//select templavoila flexform of copied page
							$res_copied = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_templavoila_flex','pages','uid='.$newID);
							if($res_copied) {
								$row_copied = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_copied);
							}
							
							//oldTV contains the flexform of the original page
							$oldTV = $row_orig['tx_templavoila_flex'];

							//newTV contains the flexform of the copied page including the original content ids and the new ones
							$newTV = $row_copied['tx_templavoila_flex'];

							//find tt_content uids in old flexform
							preg_match_all('/(<value index="vDEF">)(.*)(<\/value>)/',$oldTV,$matches);

							//if the original page had content
							if(is_array($matches) && is_array($matches[2])) {
								$res_content = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,t3_origuid','tt_content','pid='.$newID);
								$contentIds = array();
								if($res_content) {
									
									while($row_content = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_content)) {
										$contentIds[$row_content['uid']]['t3_origuid'] = $row_content['t3_origuid'];
									}
								}
								$res_old_content = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tt_content','pid='.$origID);
								$oldContentIds = array();
								if($res_old_content) {
									
									while($row_old_content = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_old_content)) {
										$oldContentIds[$row_old_content['uid']] = $row_old_content['uid'];
									}
								}
								//remove the content ids from copied flexform
								foreach($matches[2] as $old_ids) {
									$old_ids_explode = t3lib_div::trimExplode(',',$old_ids);
									foreach($old_ids_explode as $id) {
										$newTV = str_replace($id,'',$newTV);
										unset($oldContentIds[$id]);
									}
								}
								foreach($oldContentIds as $oldContentUid) {
									foreach($contentIds as $cId=>$contentValues) {
										if($oldContentUid == $contentValues['t3_origuid']) {
											
											$newTV = str_replace($cId,'',$newTV);
										}
									}
								}
								$newTV = str_replace(',,',',',$newTV);
								$newTV = str_replace('>,','>',$newTV);
								$newTV = str_replace(',<','<',$newTV);
								//write the cleaned flexform back in database
								$updateFields['tx_templavoila_flex'] = $newTV;	
								$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages','uid='.$newID,$updateFields);
							}
							
							
						}
					}
					
					$temp = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid','pages','uid='.$ins_id);
					$ins_pid = 0;
					if($temp) {
						
						$row_temp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($temp);
						$ins_pid = $row_temp['pid'];
						
					}
					//update root page with information entered by the user
					$updateFields = array();
					if($title != "") {
						$updateFields['title'] = $title;
					}
					#print_r($_POST);
					$dynamicFields = t3lib_div::_GP('data');
					print_r($dynamicFields);
					#print_r($_POST);
					if(is_array($dynamicFields)) {
						foreach($dynamicFields['pages'] as $uid) {
							$fieldsArr = $uid;
						}

						foreach($fieldsArr as $name=>$value) {
							$unique_values = t3lib_div::trimExplode(',',$value);
							$newValues = array();
							$count = 1;
							foreach($unique_values as $value) {
								#print "<br>--".$value."--<br>";
								if(substr($value,0,6) == "pages_") {
									$value = substr($value,6);
									if(substr($value,-1) == ',') {
										$value = substr($value,0,strlen($value)-1);
									}
									#print $value;
								}
								if(substr($value,0,7) == "tx_dam_") {
								$value = substr($value,7);
								if(substr($value,-1) == ',') {
									$value = substr($value,0,strlen($value)-1);
								}
								
								
								
								
								#print $value;
									$insertFields = array(
	
									    'uid_local' => $value,
									
									    'uid_foreign' => $ins_id,
									
									    'tablenames' => 'pages',
									
									    'ident' => 'tx_dampages_files',
										'sorting_foreign' => $count
									
									);
									$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dam_mm_ref',$insertFields);
									
								}
								$newValues[] = $value;
								$count++;
							
								
							}
							
							$value = implode(",",$newValues);
							
							print '$updateFields['.$name.'] = '.$value."<br>";
							$updateFields[$name] = $value;
						}
					}

					
					$dbfields = $GLOBALS['TYPO3_DB']->admin_get_fields('pages');
					$dbfields = array_keys($dbfields);
					foreach($updateFields as $field=>$value) {
						if(!in_array($field,$dbfields)) {
							unset($updateFields[$field]);
						}
					}
					unset($updateFields['tx_templavoila_flex']);
					if($this->pageOnly == 1) {
						$updateFields['tx_templavoila_flex'] = "";
					}
					//update the page title
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages','uid='.$ins_id,$updateFields);
					
					
	
					//print edit in be and new page links
					$content.= '
						<script type="text/javascript">					
							function editBE(id) {
								// Function, loading the list frame from navigation tree:
								var theUrl = top.TS.PATH_typo3+top.currentSubScript+"?id="+id;
								parent.frames[0].location.href="'.$this->doc->backPath.'alt_db_navframe.php";
								document.location.href=theUrl;
								
																
							}
						</script>';
					
					$content.= '
						<div style="padding-left:10px">
					';
					if(!$this->fe_mode) {
						$content .= '
							<div style="float:left">
								<a href="#" onclick="editBE('.$ins_id.');return false;"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' /></a>
							</div>
							<div style="float:left;height:16px;padding-top:2px;">
								<a href="#" onclick="editBE('.$ins_id.');return false;"><span style="padding-left:5px;">'.$LANG->getLL('edit_be').'</span></a>
							</div>
							<div style="clear:both"></div>
						';
					}
					$content .= '	
							<div style="float:left">
								<a href="'.$BACK_PATH.'../index.php?id='.$ins_id.'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','').' /></a>
							</div>
							<div style="float:left;height:16px;padding-top:2px;">
								<a href="'.$BACK_PATH.'../index.php?id='.$ins_id.'" target="_blank"><span style="padding-left:5px;">'.$LANG->getLL('open_fe').'</span></a>
							</div>
							<div style="clear:both"></div>
							<div style="float:left">
								<a href="index.php?id='.$this->id.'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' /></a>
							</div>
					';
					if($this->fe_mode) {
						$content .= '
								<div style="float:left;height:16px;padding-top:2px;">	
									<a href="index.php?id='.$this->id.'&pagewizard_fe_mode=1"><span style="padding-left:5px;">'.$LANG->getLL('create_another').'</span></a>
								</div>
								<div style="clear:both"></div>
						';
					} else {
						$content .= '
								<div style="float:left;height:16px;padding-top:2px;">	
									<a href="index.php?id='.$this->id.'"><span style="padding-left:5px;">'.$LANG->getLL('create_another').'</span></a>
								</div>
								<div style="clear:both"></div>
						';
					}
					$content .= '
						</div>
					';
						//reload page tree
					t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
					return $content;
				}
				
				/**
				 * Copying pages
				 * Main function for copying pages.
				 *
				 * @param	integer		Page UID to copy
				 * @param	integer		Destination PID: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
				 * @return	void
				 */
				function copyPages($uid,$destPid,$rootCopyTablesArray,$copyTablesArray)	{
			
						// Initialize:
					$uid = intval($uid);
					$destPid = intval($destPid);
			
						// Finding list of tables to copy.
					#$copyTablesArray = $this->admin ? $this->compileAdminTables() : explode(',',$this->BE_USER->groupData['tables_modify']);	// These are the tables, the user may modify
					$rootCopyTablesArray = array_unique($rootCopyTablesArray);
					$copyTablesArray = array_unique($copyTablesArray);
					#print_r($copyTablesArray);
						// Begin to copy pages if we're allowed to:
					if ($this->tcemain->admin || in_array('pages',$copyTablesArray))	{
					
							// Copy this page we're on. And set first-flag (this will trigger that the record is hidden if that is configured)!
						$theNewRootID = $this->tcemain->copySpecificPage($uid,$destPid,$rootCopyTablesArray);
			
							// If we're going to copy recursively...:
						if ($theNewRootID && $this->tcemain->copyTree)	{
			
								// Get ALL subpages to copy (read-permissions are respected!):
							$CPtable = $this->tcemain->int_pageTreeInfo(Array(), $uid, intval($this->tcemain->copyTree), $theNewRootID);
			
								// Now copying the subpages:
							foreach($CPtable as $thePageUid => $thePagePid)	{
								$newPid = $this->tcemain->copyMappingArray['pages'][$thePagePid];
								if (isset($newPid))	{
									$sub_id = $this->tcemain->copySpecificPage($thePageUid,$newPid,$copyTablesArray);
									$this->newIds[$thePageUid] = $sub_id;
									if(t3lib_extmgm::isLoaded('templavoila') && $this->pageOnly == 1) {
										$updateFields['tx_templavoila_flex'] = "";
										$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages','uid='.$sub_id,$updateFields);
									}
									
									
								} else {
									
									break;
								}
							}
						}	// else the page was not copied. Too bad...
					} 
					return $theNewRootID;
				}


				/**
				 * Print the steps bar and the step header
				 *
				 * @param	int			$curr_step: The current step
				 * @param	string		$header: The index name of the header translation in locallang
				 * @return	string		header
				 */
				function printHeaderStuff($curr_step,$header) {
					global $LANG;

					//print steps bar
					$lastStep = 4;
					if($this->fe_mode) {
						$lastStep = 3;
					}
					$content = $this->getStepsBar($curr_step,$lastStep);

					//print header
					
					$content.='
					<div style="padding-left:10px">
						<h3>'.htmlspecialchars($LANG->getLL($header)).':</h3>
					</div><br />
					';
					

					return $content;
				}
				

				/**
				 * copied from dam_index
				 *
				 * Returns HTML of a box with a step counter and "back" and "next" buttons
				 *
				 * @param	integer		current step (begins with 1)
				 * @param	integer		last step
				 * @return	string		steps bar
				 */
				function getStepsBar($currentStep, $lastStep) {
					if($this->fe_mode) {
						if($currentStep == 3) {
							$currentStep = 2;
						} elseif($currentStep == 4) {
							$currentStep = 3;
						}
					}

					$bgcolor = '#c1d5ba';
					$nrcolor = t3lib_div::modifyHTMLcolor($bgcolor,30,30,30);

					$content='';
					$buttons='';

					for ($i = 1; $i <= $lastStep; $i++) {
						$color = ($i == $currentStep) ? '#000' : $nrcolor ;
						$content.= '<span style="margin-left:5px; margin-right:5px; color:'.$color.';">'.$i.'</span>';
					}
					$content = '<span style="margin-left:50px; margin-right:25px; vertical-align:middle; font-family:Verdana,Arial,Helvetica; font-size:22px; font-weight:bold;">'.$content.'</span>';

					$content = '<div style="text-align:center;padding:4px; border-bottom:1px solid #eee; background:'.$bgcolor.';">'.$content.'</div>';

					return $content;
				}
			}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagewizard/cm1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagewizard/cm1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_pagewizard_cm1');
$SOBE->init();


$SOBE->main();
$SOBE->printContent();

?>