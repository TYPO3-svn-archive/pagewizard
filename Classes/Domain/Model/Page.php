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
 * A page
 *
 * @author   Michiel Roos <michiel@maxserv.nl>
 * @package TYPO3
 * @subpackage pagewizard
 */
class Tx_Pagewizard_Domain_Model_Page extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string
	 */
	protected $abstract;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var integer
	 */
	protected $doktype;

	/**
	 * @var integer
	 */
	protected $mountPid;

	/**
	 * @var string
	 */
	protected $keywords;

	/**
	 * @var string
	 */
	protected $media;

	/**
	 * overlay
	 *
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_TueCe_Domain_Model_PagesLanguageOverlay>
	 */
	protected $overlay;

	/**
	 * @var string
	 */
	protected $subtitle;

	/**
	 * @var DateTime
	 */
	protected $starttime;

	/**
	 * @var DateTime
	 */
	protected $stoptime;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @return mixed
	 */
	public function getAbstract() {
		return $this->abstract;
	}

	/**
	 * @return mixed
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return mixed
	 */
	public function getDoktype() {
		return $this->doktype;
	}

	/**
	 * @return int
	 */
	public function getMountPid() {
		return $this->mountPid;
	}

	/**
	 * @return mixed
	 */
	public function getKeywords() {
		return $this->keywords;
	}

	/**
	 * @return mixed
	 */
	public function getMedia() {
		return $this->media;
	}

	/**
	 * @return DateTime
	 */
	public function getStarttime() {
		return $this->starttime;
	}

	/**
	 * @return \DateTime
	 */
	public function getStoptime() {
		return $this->stoptime;
	}

	/**
	 * @return mixed
	 */
	public function getSubtitle() {
		return $this->subtitle;
	}

	/**
	 * @return mixed
	 */
	public function getTitle() {
		return $this->title;
	}

}

?>