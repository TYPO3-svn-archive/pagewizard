<?php
/*****************************************************************************
 *  Copyright notice
 *
 *  ⓒ 2013 Michiel Roos <michiel@maxserv.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is free
 *  software; you can redistribute it and/or modify it under the terms of the
 *  GNU General Public License as published by the Free Software Foundation;
 *  either version 2 of the License, or (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful, but
 *  WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 *  or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 *  more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ****************************************************************************/
/**
 * Created by PhpStorm.
 * Author: Michiel Roos <michiel@maxserv.nl>
 * Date: 06/11/13
 * Time: 10:16
 */

/**
 * PageWizard controller
 *
 * @author Michiel Roos <michiel@maxserv.nl>
 * @package TYPO3
 * @subpackage pagewizard
 */
class Tx_Pagewizard_Controller_PageWizardController extends
	Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var Tx_Pagewizard_Domain_Repository_PageRepository
	 */
	protected $pageRepository;

	/**
	 * inject the pageRepository
	 *
	 * @param Tx_Pagewizard_Domain_Repository_PageRepository $pageRepository
	 *
	 * @return void
	 */
	public function injectPageRepository(Tx_Pagewizard_Domain_Repository_PageRepository $pageRepository) {
		$this->pageRepository = $pageRepository;
	}

	/**
	 * Index action
	 *
	 * @return void
	 */
	public function indexAction() {
			/** @var t3lib_positionMap $positionMap */
		$positionMap = t3lib_div::makeInstance('t3lib_positionMap');

			// Feed the storagePid to the fluid template so we can give the user
			// some feedback when it has not yet been set.
		$configuration = $this->configurationManager->getConfiguration(
			Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
		);
		$values['storagePid'] = $configuration['persistence']['storagePid'];

		$get = t3lib_div::_GET();
		$id = intval($get['id']);

		if (count($get['edit']['pages'])) {
			$values['positionPid'] = key($get['edit']['pages']);
			$values['command'] = 'crPage';
		} else {
				// This code is called when an extension overrides the
				// newPageWiz.overrideWithExtension
			$values['positionPid'] = intval($get['positionPid']);
				// Can only be: crPage, hardcoded in: class.t3lib_positionmap.php
			$values['command'] = $get['cmd'];
		}

		switch ($values['command']) {
			case 'crPage':
					// Show page tree templates
				$pages = $this->pageRepository->findAll();

					// Initialize tree object:
					/** @var t3lib_browsetree $tree */
				$tree = t3lib_div::makeInstance('t3lib_browsetree');
					// Also store tree prefix markup:
				$tree->makeHTML = 2;
				$tree->init();
				$tree->ext_IconMode = TRUE;
				$tree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
				$tree->showDefaultTitleAttribute = TRUE;
				$tree->thisScript = t3lib_div::getIndpEnv('REQUEST_URI');

					/** @var Tx_Pagewizard_Domain_Model_Page $page */
				foreach ($pages as $page) {
					$uid = $page->getUid();
					$values['pageTemplates'][$uid]['page'] = $page;

						// Set starting page id of the tree (overrides webmounts):
					if ($uid > 0) {
						$tree->MOUNTS = array(0 => $uid);
					}
					$tree->setTreeName('pagewizard_' . $uid);

						// Creating top icon; the current page
					$tree->getBrowsableTree();

					$values['pageTemplates'][$uid]['tree'] = $this->processExpandCollapseLinks($tree->printTree(), $uid);
				}

				break;
			default:
					// Show position chooser
				$pageRecord = t3lib_BEfunc::readPageAccess($id, $GLOBALS['BE_USER']->getPagePermsClause(1));

				$values['positionMap'] = $this->processPositionMapLinks($positionMap->positionTree(
					t3lib_div::_GET('id'),
					$pageRecord,
					$GLOBALS['BE_USER']->getPagePermsClause($id),
					t3lib_div::getIndpEnv('REQUEST_URI')
				));
		}
		$this->view->assignMultiple($values);
	}

	/**
	 * Create action
	 *
	 * @return void
	 */
	public function createAction() {
		$arguments = $this->request->getArguments();

			// This part is modeled after alt_doc.php

			/** @var t3lib_TCEmain $tce */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->start('', '', $GLOBALS['BE_USER']);
		$tce->stripslashes_values = 0;

			// Update the page tree after the copy operation
		t3lib_BEfunc::setUpdateSignal('updatePageTree');

		if ($arguments['copyMode'] === 'pagesOnly') {
			$copyTablesArray = array('pages');
		} else {
				// These are the tables, the user may modify
			$copyTablesArray = $GLOBALS['BE_USER']->user['admin'] ?
				$tce->compileAdminTables() :
				explode(',', $GLOBALS['BE_USER']->groupData['tables_modify']);
			$copyTablesArray = array_unique($copyTablesArray);
		}

			// Setting default values specific for the user:
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

			// Setting internal vars:
		if ($GLOBALS['BE_USER']->uc['neverHideAtCopy']) {
			$tce->neverHideAtCopy = 1;
		}

		if (t3lib_div::compat_version('4.6')) {
			$tce->copyTree = t3lib_utility_Math::forceIntegerInRange($GLOBALS['BE_USER']->uc['copyLevels'], 0, 100);
		} else {
			$tce->copyTree = t3lib_div::intInRange($GLOBALS['BE_USER']->uc['copyLevels'], 0, 100);
		}

			// Copy this page we're on. And set first-flag (this will trigger that
			// the record is hidden if that is configured)!
		$theNewRootId = $tce->copySpecificPage($arguments['templateId'], $arguments['positionPid'], $copyTablesArray);

			// If we're going to copy recursively...:
		if ($theNewRootId && $tce->copyTree) {

				// Get ALL subpages to copy (read-permissions are respected!):
			$CPtable = $tce->int_pageTreeInfo(Array(), $arguments['templateId'], $tce->copyTree, $theNewRootId);

				// Now copying the subpages:
			foreach ($CPtable as $thePageUid => $thePagePid) {
				$newPid = $tce->copyMappingArray['pages'][$thePagePid];
				if (isset($newPid)) {
					$tce->copySpecificPage($thePageUid, $newPid, $copyTablesArray);
				} else {
					$tce->log('pages', $arguments['templateId'], 5, 0, 1, 'Something went wrong during copying branch');
					break;
				}
			}
		} // else the page was not copied. Too bad...

			// Checking referer / executing
		$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost != $refInfo['host'] && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
			$tce->log('', 0, 0, 0, 1, "Referer host '%s' and server host '%s' did not match and veriCode was not valid either!", 1, array($refInfo['host'], $httpHost));
			debug('Error: Referer host did not match with server host.');
		} else {

				// Perform the saving operation with TCEmain:
			$tce->process_uploads($_FILES);
			$tce->process_datamap();
			$tce->process_cmdmap();
		}

			// Directly jump to edit mode of the created page
		$uri = 'alt_doc.php?returnUrl=' .
			urlencode('/typo3/sysext/cms/layout/db_layout.php?id=' . $theNewRootId) .
			'&edit[pages][' . $theNewRootId . ']=edit';
		$this->redirectToUri($uri);
	}

	/**
	 * Processes the expand/collapse links. Rewrite ?PM to &PM
	 *
	 * @param string  $content Content to be processed
	 * @param integer $uid The id of the page
	 *
	 * @return string The processed and modified content
	 */
	protected function processExpandCollapseLinks($content, $uid) {
		if (strpos($content, '?PM=') !== FALSE && $uid > 0) {
			$content = preg_replace('/\?PM/', '&PM', $content);
		}
		return $content;
	}

	/**
	 * Processes the positionMap links replaces alt_doc.php with our own module
	 * Case 1, default:
	 * window.location.href='alt_doc.php?
	 * - returnUrl=%2Ftypo3%2Fmod.php%3FM%3Dweb_PagewizardTxPagewizard%26id%3D39881&
	 * - edit[pages][39881]=new&
	 * - returnNewPageId=1
	 *
	 * Case 2, newPageWiz.overrideWithExtension was set
	 * window.location.href='../typo3conf/ext/www_tue_nl/mod1/index.php?
	 * - cmd=crPage&
	 * - positionPid=39833
	 *
	 * @param string  $content Content to be processed
	 *
	 * @return string The processed and modified content
	 */
	protected function processPositionMapLinks($content) {
		$search = array(
			'/window\.location\.href=\'([^?]*)\?/',
			'/positionPid=(-?)([0-9]+)\'/',
			'/alt_doc\.php\?/',
			'/edit\[pages\]\[(-?)([0-9]+)\]=([a-z]+)&/'
		);

		$replace = array(
			'window.location.href=\'mod.php?M=web_PagewizardTxPagewizard&amp;',
			'positionPid=$1$2&id=$2\'',
			'mod.php?M=web_PagewizardTxPagewizard&amp;',
			'edit[pages][$1$2]=$3&id=$2&amp;'
		);
		return preg_replace($search, $replace, $content);
	}
}

/**
 * Extension for the tree class that generates the tree of pages in the
 * page-wizard mode
 *
 * @author Michiel Roos <michiel@maxserv.nl>
 * @package TYPO3
 * @subpackage pagewizard
 */
class localPageTree extends t3lib_pageTree {

	/**
	 * Returns the value for the image "title" attribute
	 *
	 * @param array $row The input row array (where the key "title" is used for the title)
	 *
	 * @return string The attribute value (is htmlspecialchared() already)
	 * @see wrapIcon()
	 */
	function getTitleAttrib($row) {
		return 'uid ' . $row['uid'] . ': ' . htmlspecialchars($row['title']);
	}

	/**
	 * Determines whether to expand a branch or not.
	 * Here the branch is expanded if the current id matches the global id for
	 * the listing/new
	 *
	 * @param integer $id The ID (page id) of the element
	 *
	 * @return boolean Returns TRUE if the IDs matches
	 */
	function expandNext($id) {
		return $id == $GLOBALS['SOBE']->id ? 1 : 0;
	}
}

?>