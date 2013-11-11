<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {

		// Register the new page wizard Backend Module
	Tx_Extbase_Utility_Extension::registerModule (
			// Extension name
		'pagewizard',
			// Place in section
		'web',
			// Module name
		'tx_pagewizard',
			// Position
		'',
			// An array holding the controller-action-combinations that are accessible
			// The first controller and its first action will be the default
		array(
			'PageWizard' => 'index,create'
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:pagewizard/Resources/Public/Images/clickMenuIcon.gif',
			'labels' => 'LLL:EXT:pagewizard/Resources/Private/Language/locallang.xml',
		)
	);

		// Add the menu item to the page tree context menu
	$createPageTreeAction = '
		10 = ITEM
		10 {
			name = createPageTree
			label = LLL:EXT:pagewizard/Resources/Private/Language/locallang.xml:title
			spriteIcon = actions-page-new
			callbackAction = openCustomUrlInContentFrame
			customAttributes.contentUrl = /typo3/mod.php?M=web_PagewizardTxPagewizard&id=###ID###
		}
		20 = DIVIDER
		';

		// Context menu user default configuration
	$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= '
		options.contextMenu.table {
			virtual_root.items {
				' . $createPageTreeAction . '
			}
			pages_root.items {
				' . $createPageTreeAction . '
			}
			pages.items.1000 {
				' . $createPageTreeAction . '
			}
		}
	';

	/**
	 * The pageWizard module can currently not exist without having a menu
	 * entry. The old style html template based ones, do have this option.
	 *
	 * But . . . we like do develop in Extbase / Fluid, so we currently have a
	 * small workaround to enable Extbase / Fluid backend modules and enforce
	 * user permissions on the modules through normal user- and group rights.
	 *
	 * We can just 'display: none' the menu items since they have 'static' names
	 * bound to the id of the list item in the menu. Therefore we add our own
	 * stylesheet to the t3skin.
	 *
	 * After migration to 6.2 this can be solved more cleanly:
	 * http://forge.typo3.org/projects/typo3v4-core/repository/revisions/ecbce5ac045896510c222cbc3a24f72bc673bed8
	 */
	$TBE_STYLES['skins']['t3skin']['stylesheetDirectories']['pagewizard'] =
		'EXT:pagewizard/Resources/Public/StyleSheets/Backend/';
}
?>