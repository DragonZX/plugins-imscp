<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) Sascha Bay <info@space2place.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  JailKit
 * @copyright   Laurent Declercq <l.declercq@nuxwin.com>
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class iMSCP_Plugin_JailKit
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  JailKit
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_JailKit extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onAfterDeleteCustomer,
				iMSCP_Events::onAfterChangeDomainStatus
			),
			$this
		);
	}

	/**
	 * onBeforeInstallPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeInstallPlugin($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.2', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}

	/**
	 * Plugin installation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->createDbTable();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}

		set_page_message(tr('JailKit Plugin: This task can take few minutes. Please, be patient.'), 'warning');
	}

	/**
	 * Plugin uninstallation
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager)
	{
		set_page_message(tr('JailKit Plugin: This task can take few seconds. Please, be patient.'), 'warning');
	}

	/**
	 * Plugin update
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
	{
		set_page_message(tr('JailKit Plugin: This task can take few seconds. Please, be patient.'), 'warning');
	}

	/**
	 * Plugin activation
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		if($pluginManager->getPluginStatus($this->getName()) == 'toenable') {
			set_page_message(tr('JailKit Plugin: This task can take few seconds. Please, be patient.'), 'warning');
		}
	}

	/**
	 * Plugin deactivation
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
		$pluginStatus = $pluginManager->getPluginStatus($this->getName());

		if($pluginStatus == 'tochange' || $pluginStatus == 'todisable') {
			set_page_message( tr('JailKit Plugin: This task can take few seconds. Please, be patient.'), 'warning');
		}
	}

	/**
	 * onResellerScriptStart event listener
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation('reseller');
	}

	/**
	 * onClientScriptStart event listener
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation('client');
	}

	/**
	 * onAfterDeleteCustomer event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteCustomer($event)
	{
		exec_query(
			'UPDATE jailkit SET jailkit_status = ? WHERE admin_id = ?',
			array('todelete', $event->getParam('customerId'))
		);
	}

	/**
	 * onAfterChangeDomainStatus event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterChangeDomainStatus($event)
	{
		$customerId = $event->getParam('customerId');

		if ($event->getParam('action') == 'enable') {
			exec_query('UPDATE jailkit SET jailkit_status = ? WHERE admin_id = ?', array('ok', $customerId));

			exec_query(
				'UPDATE jailkit_login SET ssh_login_locked = ?, jailkit_login_status = ? WHERE admin_id = ?',
				array('0', 'tochange', $customerId)
			);
		} else {
			exec_query('UPDATE jailkit SET jailkit_status = ? WHERE admin_id = ?', array('disabled', $customerId));

			exec_query(
				'UPDATE jailkit_login SET ssh_login_locked = ?, jailkit_login_status = ? WHERE admin_id = ?',
				array('1', 'tochange', $customerId)
			);
		}
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		$pluginDir = PLUGINS_PATH . '/' . $this->getName();

		return array(
			'/reseller/ssh_accounts.php' => $pluginDir . '/frontend/reseller/jailkit.php',
			'/client/ssh_users.php' => $pluginDir . '/frontend/client/jailkit.php'
		);
	}

	/**
	 * Get status of item with errors
	 *
	 * @return array
	 */
	public function getItemWithErrorStatus()
	{
		$stmt = exec_query(
			"
				SELECT
					jailkit_id AS item_id, jailkit_status AS status, admin_name AS item_name, 'jailkit' AS `table`,
					'jailkit_status' AS field
				FROM
					jailkit
				WHERE
					jailkit_status NOT IN(?, ?, ?, ?, ?, ?, ?)
				UNION
				SELECT
					jailkit_login_id AS item_id, jailkit_login_status AS status, ssh_login_name AS item_name,
					'jailkit_login' AS `table`, 'jailkit_login_status'AS field
				FROM
					jailkit_login
				WHERE
					jailkit_login_status NOT IN(?, ?, ?, ?, ?, ?, ?)
			",
			array(
				'ok', 'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete',
				'ok', 'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete'
			)
		);

		if ($stmt->rowCount()) {
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return array();
	}

	/**
	 * Set status of the given plugin item to 'tochange'
	 *
	 * @param string $table Table name
	 * @param string $field Status field name
	 * @param int $itemId JailKit item unique identifier
	 * @return void
	 */
	public function changeItemStatus($table, $field, $itemId)
	{
		if ($table == 'jailkit' && $field == 'jailkit_status') {
			exec_query('UPDATE jailkit SET jailkit_status = ? WHERE jailkit_id = ?', array('tochange', $itemId));
		} elseif ($table == 'jailkit_login' && $field == 'jailkit_login_status') {
			exec_query(
				'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
				array('tochange', $itemId)
			);
		}
	}

	/**
	 * Return count of request in progress
	 *
	 * @return int
	 */
	public function getCountRequests()
	{
		$stmt = exec_query(
			'
				SELECT
				(
					(SELECT COUNT(jailkit_id) FROM jailkit WHERE jailkit_status IN (?, ?, ?, ?, ?, ?))
					+
					(SELECT COUNT(jailkit_login_id) FROM jailkit_login WHERE jailkit_login_status IN (?, ?, ?, ?, ?, ?))
				) AS cnt
			',
			array(
				'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete',
				'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete'
			)
		);

		return $stmt->fields['cnt'];
	}

	/**
	 * Setup plugin navigation
	 *
	 * @param string $uiLevel Current UI level
	 * @return void
	 */
	protected function setupNavigation($uiLevel)
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if ($uiLevel == 'reseller' && ($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
				$page->addPage(
					array(
						'label' => tr('SSH Accounts'),
						'uri' => '/reseller/ssh_accounts.php',
						'title_class' => 'users',
						'privilege_callback' => array('name' => 'resellerHasCustomers')
					)
				);
			} elseif ($uiLevel == 'client' && ($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))) {
				$page->addPage(
					array(
						'label' => tr('SSH Users'),
						'uri' => '/client/ssh_users.php',
						'title_class' => 'users',
						'privilege_callback' => array('name' => array($this, 'clientPrivilegeCalback'))
					)
				);
			}
		}
	}

	/**
	 * Client privilege callback
	 *
	 * @return bool
	 */
	public function clientPrivilegeCalback()
	{
		$stmt = exec_query('SELECT jailkit_status FROM jailkit WHERE admin_id = ?', $_SESSION['user_id']);

		if($stmt->rowCount()) {
			$jailkitStatus = $stmt->fields['jailkit_status'];

			if($jailkitStatus != 'ok') {
				if($_SERVER['SCRIPT_NAME'] == '/client/jailkit.php') {
					redirectTo('domains_manage.php');
				}

				if($_SERVER['SCRIPT_NAME'] == '/client/domains_manage.php') {
					if($jailkitStatus == 'disabled' ) {
						set_page_message(tr('SSH feature has been disabled by your reseller.'), 'warning');
					} elseif($jailkitStatus != 'toadd' && $jailkitStatus != 'todelete') {
						set_page_message(
							tr('SSH feature is currently unavailable due to maintenance operation.'), 'warning'
						);
					}
				}
			} else {
				return true;
			}
		} elseif($_SERVER['SCRIPT_NAME'] == '/client/jailkit.php') {
			showBadRequestErrorPage();
			exit;
		}

		return false;
	}

	/**
	 * Create jailkit and jailkit_login database tables
	 *
	 * @return void
	 */
	protected function createDbTable()
	{
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS jailkit (
					jailkit_id int(11) unsigned NOT NULL AUTO_INCREMENT,
					admin_id int(10) unsigned NOT NULL,
					admin_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					max_logins int(11) default NULL,
					jailkit_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (jailkit_id),
					KEY jailkit_id (jailkit_id)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			'
		);

		execute_query(
			"
				CREATE TABLE IF NOT EXISTS jailkit_login (
					jailkit_login_id int(11) unsigned NOT NULL AUTO_INCREMENT,
					admin_id int(10) unsigned NOT NULL,
					ssh_login_name varchar(16) collate utf8_unicode_ci default NULL,
					ssh_login_pass varchar(200) collate utf8_unicode_ci default NULL,
					ssh_login_locked tinyint(1) default '0',
					jailkit_login_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (jailkit_login_id),
					UNIQUE KEY ssh_login_name (ssh_login_name),
					KEY jailkit_login_id (jailkit_login_id)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			"
		);
	}
}
