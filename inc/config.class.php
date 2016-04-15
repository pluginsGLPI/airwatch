<?php
/*
 * @version $Id$
 LICENSE

  This file is part of the Airwatch plugin.

 Order plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Airwatch plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; along with Airwatch. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   airwatch
 @author    Teclib'
 @copyright Copyright (c) 2016 Teclib'
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://github.com/pluginsglpi/airwatch
 @link      http://www.glpi-project.org/
 @link      http://www.teclib-edition.com/
 @since     2016
 ---------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')){
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
      Html::autocompletionTextField($this, "fusioninventory_url");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Airwatch Service URL", "airwatch") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "airwatch_service_url");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Airwatch Console URL", "airwatch") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "airwatch_console_url");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Username", "airwatch") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "username");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Password", "airwatch") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "password");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("API Key", "airwatch") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "api_key");
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
      if (!TableExists("glpi_plugin_airwatch_configs")) {
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
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die ($DB->error());

         $tmp = array('id'                   => 1,
                      'fusioninventory_url' => 'http://localhost/glpi/plugins/fusioninventory/',
                      'airwatch_service_url' => '',
                      'airwatch_console_url' => '',
                      'username'             => '',
                      'password'             => '',
                      'api_key'              => '',
                      'skip_ssl_check'       => 0);
               $config->add($tmp);
      }
   }

   public static function uninstall() {
      global $DB;
      $DB->query("DROP TABLE IF EXISTS `glpi_plugin_airwatch_configs`");
   }
}
