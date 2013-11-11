<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "pagewizard".
 *
 * Auto generated 11-11-2013 15:09
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Page Tree Wizard',
	'description' => 'Create new page tree from a set of template page trees',
	'category' => 'be',
	'shy' => 0,
	'version' => '2.0.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Michiel Roos, Dev-Team Typoheads, Reinhard Führicht',
	'author_email' => 'extensions@maxserv.nl',
	'author_company' => 'MaxServ',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:18:{s:9:"ChangeLog";s:4:"53b0";s:16:"ext_autoload.php";s:4:"2a46";s:12:"ext_icon.gif";s:4:"1b05";s:14:"ext_tables.php";s:4:"4812";s:24:"ext_typoscript_setup.txt";s:4:"324b";s:43:"Classes/Controller/PageWizardController.php";s:4:"a7dc";s:29:"Classes/Domain/Model/Page.php";s:4:"73bc";s:44:"Classes/Domain/Repository/PageRepository.php";s:4:"f170";s:44:"Classes/ViewHelpers/Format/RawViewHelper.php";s:4:"877f";s:49:"Resources/Private/Backend/Layouts/PageWizard.html";s:4:"46ae";s:62:"Resources/Private/Backend/Templates/4.5/PageWizard/Create.html";s:4:"e74f";s:61:"Resources/Private/Backend/Templates/4.5/PageWizard/Index.html";s:4:"fd3a";s:58:"Resources/Private/Backend/Templates/PageWizard/Create.html";s:4:"e74f";s:57:"Resources/Private/Backend/Templates/PageWizard/Index.html";s:4:"5d4f";s:40:"Resources/Private/Language/locallang.xml";s:4:"1ba9";s:41:"Resources/Public/Images/clickMenuIcon.gif";s:4:"591c";s:46:"Resources/Public/StyleSheets/Backend/Style.css";s:4:"c5a9";s:14:"doc/manual.sxw";s:4:"c294";}',
	'suggests' => array(
	),
);

?>