<?php
/**
 * Created S/02/08/2014
 * Updated V/19/06/2020
 *
 * Copyright 2012-2020 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/openmage/modules
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

class Luigifab_Modules_Model_Source_Rewrites extends Varien_Data_Collection {

	public function getCollection() {

		// getName() = le nom du tag xml
		// => /config/global/models/cron/rewrite/observer
		// <global>                                          <= $config/../../../../$scope
		//  <models>                                         <= $config/../../../$type
		//   <cron>                                          <= $config/../../$module
		//    <rewrite>
		//     <observer>Luigifab_Modules_Model_Rewrite_Cron <= $config
		$config   = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes    = $config->getXpath('/config/*/*/*/rewrite/*');
		$rewrites = $this->searchAllRewrites();

		foreach ($nodes as $config) {

			$scope  = $config->getParent()->getParent()->getParent()->getParent()->getName();
			$type   = $config->getParent()->getParent()->getParent()->getName();
			$module = $config->getParent()->getParent()->getName();
			$class  = $config->getName();

			if ($type == 'routers')
				continue;

			//   class=Luigifab_Modules_Model_Rewrite_Cron
			//   first=Modules_Model_Rewrite_Cron  (variable temporaire)
			//  second=Model_Rewrite_Cron          (variable temporaire)
			// module2=Modules
			//  class2=Rewrite_Cron
			// modName=Luigifab/Modules
			$first   = mb_substr($config, mb_stripos($config, '_') + 1);
			$second  = mb_substr($first, mb_stripos($first, '_') + 1);
			$module2 = mb_substr($first, 0, mb_stripos($first, '_'));
			$class2  = mb_substr($second, mb_stripos($second, '_') + 1);

			$moduleName = mb_substr($config, 0, mb_stripos($config, '_')).'/'.$module2;

			// surcharge en conflit
			// - au moins deux fichiers config définissent plus ou moins la même chose
			// - ce qui est affiché = ce qui est actif
			$isConflict = (!empty($rewrites[$type][$module.'/'.$class]) && (count($rewrites[$type][$module.'/'.$class]) > 1));

			$item = new Varien_Object();
			$item->setData('module', $moduleName);
			$item->setData('scope', $scope);
			$item->setData('type', mb_substr($type, 0, -1));
			$item->setData('core_class', $module.'/'.$class);

			if ($isConflict) {
				$text = mb_strtolower($module2.'/'.$class2).$this->transformData($rewrites[$type][$module.'/'.$class]);
				$item->setData('rewrite_class', $text);
				$item->setData('status', 'disabled'); // disabled=conflict / enabled=ok
			}
			else {
				$item->setData('rewrite_class', mb_strtolower($module2.'/'.$class2));
				$item->setData('status', 'enabled'); // disabled=conflict / enabled=ok
			}

			$this->addItem($item);
		}

		usort($this->_items, static function ($a, $b) {
			$test = strnatcasecmp($a->getData('scope'), $b->getData('scope'));
			if ($test == 0)
				$test = strnatcasecmp($a->getData('type'), $b->getData('type'));
			if ($test == 0)
				$test = strnatcasecmp($a->getData('core_class'), $b->getData('core_class'));
			return $test;
		});

		return $this;
	}

	private function transformData($data) {

		$inline = [];
		foreach ($data as $key => $value)
			$inline[] = sprintf('<br />- %s = %s', $key, $value);

		return implode($inline);
	}

	private function searchAllRewrites() {

		$folders  = ['app/code/local/', 'app/code/community/'];
		$rewrites = [];
		$files    = [];

		foreach ($folders as $folder)
			$files = array_merge($files, glob($folder.'*/*/etc/config.xml'));

		foreach ($files as $file) {

			$dom = new DOMDocument();
			$dom->loadXML(file_get_contents($file));
			$qry = new DOMXPath($dom);
			$nodes = $qry->query('/config/*/*/*/rewrite/*');

			// => /config/global/models/cron/rewrite/observer
			// <global>
			//  <models>                                         <= $config/../../../$type
			//   <cron>                                          <= $config/../../$module
			//    <rewrite>
			//     <observer>Luigifab_Modules_Model_Rewrite_Cron <= $config
			// tagName/nodeValue === string
			foreach ($nodes as $config) {

				$type   = $config->parentNode->parentNode->parentNode->tagName;
				$module = $config->parentNode->parentNode->tagName;
				$class  = $config->tagName;

				$rewrites[$type][$module.'/'.$class][$file] = $config->nodeValue;
			}
		}

		return $rewrites;
	}
}