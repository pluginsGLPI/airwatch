<?php

/**
 * -------------------------------------------------------------------------
 * Airwatch plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Airwatch.
 *
 * Airwatch is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Airwatch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Airwatch. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2016-2022 by Teclib'.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/airwatch
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAirwatchConfig extends CommonDBTM {
   static $rightname = 'config';

   public static function getTypeName($nb = 0) {
      return __("GLPi Airwatch Connector", 'airwatch');
   }

   public function showForm() {
      $this->getFromDB(1);

      echo "<div class='center'>";
      echo "<form name='form' method='post' action='" . $this->getFormURL() . "'>";

      echo "<input type='hidden' name='id' value='1'>";

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='2'>" . __("Plugin configuration", "airwatch") . "</th></tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Service URL", "fusioninventory") . "</td>";
      echo "<td>";
      echo Html::input(
         'fusioninventory_url',
         [
            'value' => $this->fields['fusioninventory_url'],
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Airwatch Service URL", "airwatch") . "</td>";
      echo "<td>";
      echo Html::input(
         'airwatch_service_url',
         [
            'value' => $this->fields['airwatch_service_url'],
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Airwatch Console URL", "airwatch") . "</td>";
      echo "<td>";
      echo Html::input(
         'airwatch_console_url',
         [
            'value' => $this->fields['airwatch_console_url'],
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Username", "airwatch") . "</td>";
      echo "<td>";
      echo Html::input(
         'username',
         [
            'value' => $this->fields['username'],
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Password", "airwatch") . "</td>";
      echo "<td>";
      // FIXME This is a credential field. Encrypt it, and handle ability to "blank" it.
      echo Html::input(
         'password',
         [
            'type'  => 'password',
            'value' => $this->fields['password'],
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("API Key", "airwatch") . "</td>";
      echo "<td>";
      echo Html::input(
         'api_key',
         [
            'value' => $this->fields['api_key'],
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Skip SSL check", "airwatch") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("skip_ssl_check", $this->fields['skip_ssl_check']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td colspan='2' align='center'>";
      echo "<input type='submit' name='update' value=\"" . _sx("button", "Post") . "\" class='submit' >";
      echo "&nbsp;<input type='submit' name='test' value=\"" . _sx("button", "Test") . "\" class='submit' >";
      echo"</td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   //----------------- Install & uninstall -------------------//
   public static function install(Migration $migration) {
      global $DB;

      $config = new self();

      //This class is available since version 1.3.0
      if (!$DB->tableExists("glpi_plugin_airwatch_configs")) {
         $migration->displayMessage("Install glpi_plugin_airwatch_configs");

         //Install
         $query = "CREATE TABLE `glpi_plugin_airwatch_configs` (
                     `id` int(11) NOT NULL auto_increment,
                     `fusioninventory_url` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                     `airwatch_service_url` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                     `airwatch_console_url` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                     `username` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                     `password` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                     `api_key` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                     `skip_ssl_check` tinyint(1) NOT NULL default '0',
                     PRIMARY KEY  (`id`),
                     KEY `fusioninventory_url` (`fusioninventory_url`),
                     KEY `airwatch_service_url` (`airwatch_service_url`),
                     KEY `airwatch_console_url` (`airwatch_console_url`),
                     KEY `username` (`username`),
                     KEY `password` (`password`),
                     KEY `api_key` (`api_key`),
                     KEY `skip_ssl_check` (`skip_ssl_check`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die ($DB->error());

         $tmp = ['id'                   => 1,
                 'fusioninventory_url' => 'http://localhost/glpi/plugins/fusioninventory/',
                 'airwatch_service_url' => '',
                 'airwatch_console_url' => '',
                 'username'             => '',
                 'password'             => '',
                 'api_key'              => '',
                 'skip_ssl_check'       => 0];
         $config->add($tmp);
      }
   }

   public static function uninstall() {
      global $DB;
      $DB->query("DROP TABLE IF EXISTS `glpi_plugin_airwatch_configs`");
   }

   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'fusioninventory_url',
         'name'               => __('Service URL', 'fusioninventory'),
         'datatype'           => 'string',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'airwatch_service_url',
         'name'               => __('Airwatch Service URL', 'airwatch'),
         'datatype'           => 'string',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'airwatch_console_url',
         'name'               => __('Airwatch Console URL', 'airwatch'),
         'datatype'           => 'string',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      return $tab;
   }
}
