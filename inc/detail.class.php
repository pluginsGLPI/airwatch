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
 @since     2016
 ---------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class PluginAirwatchDetail extends CommonDBChild {

      // From CommonDBChild
      static public $itemtype = 'Computer';
      static public $items_id = 'computers_id';
      public $dohistory       = false;


      static function getTypeName($nb=0) {
         return __('Airwatch');
      }

      /**
       * @see CommonGLPI::getTabNameForItem()
      **/
      function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

         // can exists for template
         if (($item->getType() == 'Computer')
             && Computer::canView()) {
            $nb = countElementsInTable('glpi_plugin_airwatch_details',
                                       "computers_id = '".$item->getID()."'");
            if (!$nb) {
               return '';
            } else {
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
         }
         return '';
      }


      /**
       * @param $item            CommonGLPI object
       * @param $tabnum          (default 1)
       * @param $withtemplate    (default 0)
       */
      static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
         self::showForComputer($item, $withtemplate);
         return true;
      }


      function getFromDBbComputerID($computers_id) {
         global $DB;

         $query = "SELECT `id` FROM `glpi_plugin_airwatch_details`
                   WHERE `computers_id`='$computers_id'";

         $result = $DB->query($query);
         if ($DB->numrows($result)) {
            $id = $DB->result($result, 0, 'id');
            $this->getFromDB($id);
            return true;
         } else {
            return false;
         }
      }

      static function showForComputer(CommonDBTM $item, $withtemplate='') {

         $detail = new self();
         if (!$detail->getFromDBbComputerID($item->getID())) {
            return true;
         }

         echo "<div class='center'>";
         echo "<form name='form' method='post' action='" . $detail->getFormURL() . "'>";

         echo "<table class='tab_cadre_fixe'>";

         echo "<tr><th colspan='4'>" . __("General", "airwatch") . "</th></tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Imei", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['imei'];
         echo "</td>";

         echo "<td>" . __("Airwatch ID", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['aw_device_id'];
         echo "<input type='hidden' name='aw_device_id' value='".$detail->fields['aw_device_id']."'>";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Phone number", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['phone_number'];
         echo "<td>" . __("Last seen", "airwatch") . "</td>";
         echo "<td>";
         echo Html::convDateTime($detail->fields['date_last_seen']);
         if ($detail->fields['date_last_seen']) {
            $date1 = date_create($_SESSION['glpi_currenttime']);
            $date2 = date_create($detail->fields['date_last_seen']);
            $interval = date_diff($date1, $date2);
            echo "&nbsp;(".$interval->format('%h Hours %i Minute %s Seconds').")";            
         }
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Data encryption enabled", "airwatch") . "</td>";
         echo "<td>";
         echo Dropdown::getYesNo($detail->fields['is_dataencryption']);
         echo "</td>";
         echo "<td>" . __("Current SIM serial number", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['simcard_serial'];
         echo "</td>";
         echo "</tr>";

         //If airwatch console url is set, display a link
         $config = new PluginAirwatchConfig();
         $config->getFromDB(1);
         if ($config->fields['airwatch_console_url'] > '' && $detail->fields['aw_device_id'] > 0) {
            echo "<tr class='tab_bg_1' align='center'>";
            $url = $config->fields['airwatch_console_url'].
                   '/#/Airwatch/Device/Details/Summary/'.
                   $detail->fields['aw_device_id'];
            echo "<td colspan='4' align='center'>";
            echo "<a href='$url' target=_blank>".__("See device in Airwatch console", "airwatch")."</td>";
            echo "</tr>";
         }

         echo "<tr><th colspan='4'>" . __("Enrollment process", "airwatch") . "</th></tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Enrollment status", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['enrollmentstatus'];
         echo "</td>";
         echo "<td>" . __("Last enrollment date", "airwatch") . "</td>";
         echo "<td>";
         echo Html::convDateTime($detail->fields['date_last_enrollment']);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Last enrollment check", "airwatch") . "</td>";
         echo "<td>";
         echo Html::convDateTime($detail->fields['date_last_enrollment']);
         echo "</td>";
         echo "</td><td colspan='2'></td>";
         echo "</tr>";

         echo "<tr><th colspan='4'>" . __("Other status checks", "airwatch") . "</th></tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Compliance status", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['compliancestatus'];
         echo "</td>";
         echo "<td>" . __("Last compliance check date", "airwatch") . "</td>";
         echo "<td>";
         echo Html::convDateTime($detail->fields['date_last_compliance_check']);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Compromised status", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['compromisedstatus'];
         echo "</td>";
         echo "<td>" . __("Last compromised check date", "airwatch") . "</td>";
         echo "<td>";
         echo Html::convDateTime($detail->fields['date_last_compromised_check']);
         echo "</td>";
         echo "</tr>";

         echo "<tr><th colspan='4'>" . __("Roaming", "airwatch") . "</th></tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Roaming enabled", "airwatch") . "</td>";
         echo "<td>";
         echo Dropdown::getYesNo($detail->fields['is_roaming_enabled']);
         echo "</td>";
         echo "<td>" . __("Data roaming enabled", "airwatch") . "</td>";
         echo "<td>";
         echo Dropdown::getYesNo($detail->fields['is_data_roaming_enabled']);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Voice roaming enabled", "airwatch") . "</td>";
         echo "<td>";
         echo Dropdown::getYesNo($detail->fields['is_voice_roaming_enabled']);
         echo "</td>";
         echo "<td colspan='2'>";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td colspan='4' align='center'>";
         echo "<input type='submit' name='update' value=\"" .
            _sx("button", "Refresh inventory now") . "\" class='submit' >";
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
         if (!TableExists("glpi_plugin_airwatch_details")) {
            $migration->displayMessage("Install glpi_plugin_airwatch_details");

            //Install
            $query = "CREATE TABLE `glpi_plugin_airwatch_details` (
                        `id` int(11) NOT NULL auto_increment,
                        `computers_id` int(11) NOT NULL DEFAULT '0',
                        `aw_device_id` int(11) NOT NULL DEFAULT '0',
                        `imei` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '',
                        `simcard_serial` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '',
                        `phone_number` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '',
                        `date_mod` datetime DEFAULT NULL,
                        `date_creation` datetime DEFAULT NULL,
                        `date_last_seen` datetime DEFAULT NULL,
                        `date_last_enrollment` datetime DEFAULT NULL,
                        `date_last_enrollment_check` datetime DEFAULT NULL,
                        `date_last_compliance_check` datetime DEFAULT NULL,
                        `date_last_compromised_check` datetime DEFAULT NULL,
                        `enrollmentstatus` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '',
                        `compliancestatus` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '',
                        `compromisedstatus` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '',
                        `is_dataencryption` tinyint(1) NOT NULL DEFAULT '0',
                        `is_roaming_enabled` tinyint(1) NOT NULL DEFAULT '0',
                        `is_data_roaming_enabled` tinyint(1) NOT NULL DEFAULT '0',
                        `is_voice_roaming_enabled` tinyint(1) NOT NULL DEFAULT '0',
                        PRIMARY KEY  (`id`)
                     ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->query($query) or die ($DB->error());
      }
   }

      public static function uninstall() {
         global $DB;
         $DB->query("DROP TABLE IF EXISTS `glpi_plugin_airwatch_details`");
      }

}
