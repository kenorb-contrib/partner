<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */
class CRM_Core_Extensions_Module {
  public function __construct($ext) {
    $this->ext = $ext;

    $this->config = CRM_Core_Config::singleton();
  }

  public function install() {
    self::commonInstall('install');
    self::commonInstall('enable');
  }

  private function callHook($moduleName, $modulePath, $hookName) {
    include_once ($modulePath . DIRECTORY_SEPARATOR . $moduleName . '.php');
    $fnName = "{$moduleName}_civicrm_{$hookName}";
    if (function_exists($fnName)) {
      $fnName();
    }
  }

  private function commonInstall($type = 'install') {
    $this->callHook($this->ext->file,
      $this->ext->path,
      $type
    );
  }

  public function uninstall() {
    $this->commonUNInstall('uninstall');
    return TRUE;
  }

  private function commonUNInstall($type = 'uninstall') {
    $this->callHook($this->ext->file,
      $this->ext->path,
      $type
    );
  }

  public function disable() {
    $this->commonUNInstall('disable');
  }

  public function enable() {
    $this->commonInstall('enable');
  }
}

