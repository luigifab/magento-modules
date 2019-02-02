<?php
/**
 * Created L/21/07/2014
 * Updated M/15/01/2019
 *
 * Copyright 2012-2019 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/magento/modules
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

class Luigifab_Modules_Block_Adminhtml_Modules_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_modules_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(true);

		$this->setCollection(Mage::getModel('modules/source_modules')->getCollection());
	}

	protected function _prepareCollection() {
		//$this->setCollection() dans __construct() pour getCount()
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {

		$this->addColumn('name', array(
			'header'    => $this->__('Module name'),
			'index'     => 'name',
			'filter'    => false,
			'sortable'  => false,
			'frame_callback' => array($this, 'decorateName')
		));

		$this->addColumn('code_pool', array(
			'header'    => $this->__('Type'),
			'index'     => 'code_pool',
			'align'     => 'center',
			'width'     => '130px',
			'filter'    => false,
			'sortable'  => false
		));

		$this->addColumn('current_version', array(
			'header'    => $this->__('Installed version'),
			'index'     => 'current_version',
			'align'     => 'center',
			'width'     => '130px',
			'filter'    => false,
			'sortable'  => false
		));

		$this->addColumn('last_version', array(
			'header'    => $this->__('Last version'),
			'index'     => 'last_version',
			'align'     => 'center',
			'width'     => '130px',
			'filter'    => false,
			'sortable'  => false
		));

		$this->addColumn('last_date', array(
			'header'    => $this->__('Latest version of'),
			'index'     => 'last_date',
			'type'      => 'date',
			'format'    => Mage::getSingleton('core/locale')->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG),
			'align'     => 'center',
			'width'     => '180px',
			'filter'    => false,
			'sortable'  => false
		));

		$this->addColumn('status', array(
			'header'    => $this->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'options'   => array(
				'uptodate' => $this->__('Up to date'),
				'toupdate' => $this->__('To update'),
				'beta'     => $this->__('Beta'),
				'unknown'  => '?',
				'disabled' => $this->__('Disabled')
			),
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'frame_callback' => array($this, 'decorateStatus')
		));

		return parent::_prepareColumns();
	}

	public function getCount() {
		return $this->getCollection()->getSize();
	}


	public function getRowClass($row) {
		return ($row->getData('status') == 'disabled') ? 'disabled' : '';
	}

	public function getRowUrl($row) {
		return false;
	}

	public function canDisplayContainer() {
		return false;
	}

	public function getMessagesBlock() {
		return Mage::getBlockSingleton('core/template');
	}


	public function decorateStatus($value, $row, $column, $isExport) {
		return sprintf('<span class="modules-status grid-%s">%s</span>', $row->getData('status'), $value);
	}

	public function decorateName($value, $row, $column, $isExport) {
		return !empty($url = $row->getData('url')) ? sprintf('<a href="%s">%s</a>', $url, $value) : $value;
	}
}