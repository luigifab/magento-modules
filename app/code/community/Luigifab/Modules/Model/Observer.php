<?php
/**
 * Created S/22/11/2014
 * Updated M/08/11/2016
 *
 * Copyright 2012-2017 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://redmine.luigifab.info/projects/magento/wiki/modules
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

class Luigifab_Modules_Model_Observer extends Luigifab_Modules_Helper_Data {

	// EVENT admin_system_config_changed_section_modules
	public function updateConfig() {

		try {
			$config = Mage::getModel('core/config_data');
			$config->load('crontab/jobs/modules_send_report/schedule/cron_expr', 'path');

			if (Mage::getStoreConfigFlag('modules/email/enabled')) {

				// hebdomadaire, tous les lundi à 1h00 (hebdomadaire/weekly)
				// minute hour day-of-month month-of-year day-of-week (Dimanche = 0, Lundi = 1...)
				// 0	     1    *            *             0|1         => weekly
				$config->setValue('0 1 * * '.Mage::getStoreConfig('general/locale/firstday'));
				$config->setPath('crontab/jobs/modules_send_report/schedule/cron_expr');
				$config->save();

				// email de test
				// s'il n'a pas déjà été envoyé dans la dernière heure (3600 secondes)
				// ou si le cookie maillog_print_email est présent, et ce, quoi qu'il arrive
				$cookie = (Mage::getSingleton('core/cookie')->get('maillog_print_email') === 'yes') ? true : false;
				$session = Mage::getSingleton('admin/session')->getLastModulesReport();
				$timestamp = Mage::getSingleton('core/date')->timestamp();

				if (is_null($session) || ($timestamp > ($session + 3600)) || $cookie) {
					$this->sendEmailReport();
					Mage::getSingleton('admin/session')->setLastModulesReport($timestamp);
				}
			}
			else {
				$config->delete();
			}
		}
		catch (Exception $e) {
			Mage::throwException($e->getMessage());
		}
	}


	// CRON modules_send_report
	public function sendEmailReport() {

		Mage::getSingleton('core/translate')->setLocale(Mage::getStoreConfig('general/locale/code'))->init('adminhtml', true);
		$modules = Mage::getModel('modules/source_modules')->getCollection();
		$updates = array();

		foreach ($modules as $module) {

			if ($module->getStatus() !== 'toupdate')
				continue;

			array_push($updates, sprintf('(%d) <strong>%s %s</strong><br/>➩ %s (%s)', count($updates) + 1, $module->getName(), $module->getCurrentVersion(), $module->getLastVersion(), $module->getLastDate()));
		}

		$this->sendReportToRecipients(array('list' => (count($updates) > 0) ? implode('</li><li style="margin:0.8em 0 0.5em;">', $updates) : ''));
	}

	private function getEmailUrl($url, $params = array()) {

		if (Mage::getStoreConfigFlag('web/seo/use_rewrites'))
			return preg_replace('#/[^/]+\.php/#', '/', Mage::helper('adminhtml')->getUrl($url, $params));
		else
			return preg_replace('#/[^/]+\.php/#', '/index.php/', Mage::helper('adminhtml')->getUrl($url, $params));
	}

	private function sendReportToRecipients($vars) {

		$emails = explode(' ', trim(Mage::getStoreConfig('modules/email/recipient_email')));
		$vars['config'] = $this->getEmailUrl('adminhtml/system/config');
		$vars['config'] = substr($vars['config'], 0, strrpos($vars['config'], '/system/config'));

		foreach ($emails as $email) {

			if (in_array($email, array('hello@example.org', 'hello@example.com', '')))
				continue;

			// sendTransactional($templateId, $sender, $recipient, $name, $vars = array(), $storeId = null)
			$template = Mage::getModel('core/email_template');
			$template->sendTransactional(
				Mage::getStoreConfig('modules/email/template'),
				Mage::getStoreConfig('modules/email/sender_email_identity'),
				trim($email), null, $vars
			);

			if (!$template->getSentSuccess())
				Mage::throwException($this->__('Can not send the report by email to %s.', $email));

			//exit($template->getProcessedTemplate($vars));
		}
	}
}