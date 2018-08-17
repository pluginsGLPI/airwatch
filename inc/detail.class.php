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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
* Store and display Airwatch informations for a device
*/
class PluginAirwatchDetail extends CommonDBTM {

      //Do not record historical, because details are deleted and recreated at each inventory
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
         $nb = countElementsInTable(
            'glpi_plugin_airwatch_details',
            ['computers_id' => $item->getID()]
         );
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
      echo "<td>" . __("Serial number") . "</td>";
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
      if ($detail->fields['date_last_seen']) {
         echo self::getHumanReadableDate($_SESSION['glpi_currenttime'],
                                         $detail->fields['date_last_seen'],
                                         1);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Data encryption enabled", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_dataencryption']);
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
         echo "<a href='$url' target=_blank>"
            .__("See device in Airwatch console", "airwatch")."</td>";
         echo "</tr>";
      }

      echo "<tr><th colspan='4'>" . __("Enrollment process", "airwatch") . "</th></tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Enrollment status", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_enrolled']);
      echo "</td>";
      echo "<td>" . __("Last enrollment date", "airwatch") . "</td>";
      echo "<td>";
      if ($detail->fields['date_last_enrollment']) {
         echo self::getHumanReadableDate($_SESSION['glpi_currenttime'],
                                         $detail->fields['date_last_enrollment'],
                                         1);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Last enrollment check", "airwatch") . "</td>";
      echo "<td>";
      if ($detail->fields['date_last_enrollment_check']) {
         echo self::getHumanReadableDate($_SESSION['glpi_currenttime'],
                                         $detail->fields['date_last_enrollment_check'],
                                         1);
      }
      echo "</td>";
      echo "</td><td colspan='2'></td>";
      echo "</tr>";

      echo "<tr><th colspan='4'>" . __("Other status checks", "airwatch") . "</th></tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Compliance status", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_compliant'], true);
      echo "</td>";
      echo "<td>" . __("Last compliance check date", "airwatch") . "</td>";
      echo "<td>";
      if ($detail->fields['date_last_compliance_check']) {
         echo self::getHumanReadableDate($_SESSION['glpi_currenttime'],
                                         $detail->fields['date_last_compliance_check'],
                                         1);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Compromised status", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_compromised']);
      echo "</td>";
      echo "<td>" . __("Last compromised check date", "airwatch") . "</td>";
      echo "<td>";
      if ($detail->fields['date_last_compromised_check']) {
         echo self::getHumanReadableDate($_SESSION['glpi_currenttime'],
                                         $detail->fields['date_last_compromised_check'],
                                         1);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr><th colspan='4'>" . __("Roaming", "airwatch") . "</th></tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Roaming enabled", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_roaming_enabled']);
      echo "</td>";
      echo "<td>" . __("Data roaming enabled", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_data_roaming_enabled']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";
      echo "<td>" . __("Voice roaming enabled", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_voice_roaming_enabled']);
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "</tr>";

      PluginAirwatchCompliance::showForComputer($item, $withtemplate);

      //To refresh an inventory, you must be able to update a computer
      if (self::canRefresh()) {
         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td colspan='4' align='center'>";
         echo "<input type='submit' name='update' value=\"" .
            _sx("button", "Refresh inventory now") . "\" class='submit' >";
         echo"</td>";
         echo "</tr>";
      }

      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }

      /**
      * @since 0.90+1.0
      *
      * Delete airwatch details when a computer is purged
      * @param computer the Computer object
      */
   static function cleanOnPurge(Computer $computer) {
      $detail = new self();
      $detail->deleteByCriteria(array('computers_id' => $computer->getID()));
   }

      /**
      * Display informations about computer (bios...)
      *
      * @param type $computers_id
      */
   static function showInfo($item) {
      global $CFG_GLPI;

      $detail = new self();
      if (!$detail->getFromDBbComputerID($item->getID())) {
         return true;
      }

      echo '<table class="tab_glpi" width="100%">';
      echo '<tr>';
      echo '<th colspan="2">'.__('Airwatch', 'airwatch').'</th>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Phone number', 'airwatch');
      echo '</td>';
      echo '<td>';
      echo $detail->fields['phone_number'];
      echo '</td>';
      echo '</tr>';

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Compliance status", "airwatch") . "</td>";
      echo "<td>";
      echo self::showYesNoNotSet($detail->fields['is_compliant']);
      echo "</td>";
      echo '</tr>';

      echo '</table>';
   }

      /**
       * Get human readable time difference between 2 dates
       *
       * Return difference between 2 dates in year, month, hour, minute or second
       * The $precision caps the number of time units used: for instance if
       * $time1 - $time2 = 3 days, 4 hours, 12 minutes, 5 seconds
       * - with precision = 1 : 3 days
       * - with precision = 2 : 3 days, 4 hours
       * - with precision = 3 : 3 days, 4 hours, 12 minutes
       *
       * From: http://www.if-not-true-then-false.com/2010/php-calculate-real-differences-between-two-dates-or-timestamps/
       *
       * @param mixed $time1 a time (string or timestamp)
       * @param mixed $time2 a time (string or timestamp)
       * @param integer $precision Optional precision
       * @return string time difference
       */
   static function getHumanReadableDate( $time1, $time2, $precision = 2 ) {
      // If not numeric then convert timestamps
      if (!is_int( $time1 )) {
              $time1 = strtotime( $time1 );
      }
      if (!is_int( $time2 )) {
              $time2 = strtotime( $time2 );
      }

      // If time1 > time2 then swap the 2 values
      if ($time1 > $time2) {
              list( $time1, $time2 ) = array( $time2, $time1 );
      }

      // Set up intervals and diffs arrays
      $intervals = array('year', 'month', 'day', 'hour', 'minute', 'second' );
      $diffs = array();

      foreach ($intervals as $interval) {
              // Create temp time from time1 and interval
              $ttime = strtotime( '+1 ' . $interval, $time1 );
              // Set initial values
              $add = 1;
              $looped = 0;
              // Loop until temp time is smaller than time2
         while ($time2 >= $ttime) {
            // Create new temp time from time1 and interval
            $add++;
            $ttime = strtotime( "+" . $add . " " . $interval, $time1 );
            $looped++;
         }

              $time1 = strtotime( "+" . $looped . " " . $interval, $time1 );
              $diffs[ $interval ] = $looped;
      }

      $count = 0;
      $times = array();
      foreach ($diffs as $interval => $value) {
              // Break if we have needed precission
         if ($count >= $precision) {
            break;
         }
              // Add value and interval if value is bigger than 0
         if ($value > 0) {
                // Add value and interval to times array
            $times[] = Dropdown::getValueWithUnit($value, $interval);
            $count++;
         }
      }

      // Return string with times
      return implode( ", ", $times );
   }

      /**
      * since 0.90+1.1
      *
      * Delete all profiles informations for a device
      * @param computers_id GLPi device ID
      */
   static function deleteForComputer($computers_id) {
      global $DB;

      $query = "DELETE FROM `glpi_plugin_airwatch_airwatchprofiles`
                   WHERE `computers_id`='$computers_id'";
      $DB->query($query);
   }

   static function canRefresh() {
      return (PluginAirwatchRest::testConnection() && Computer::canUpdate());
   }

   static function showYesNoNotSet($value, $show_warning = false) {
      switch ($value) {
         case -1:
            return "<img src='../pics/ok2.png' title='".__('None')."'>";
         case 0:
            if ($show_warning) {
               return "<img src='../pics/ko_min.png' title='".__('Error')."'>";
            }
            return "<img src='../pics/reset.png' title='".__('No')."'>";
         case 1:
            return "<img src='../pics/ok_min.png' title='".__('Yes')."'>";
      }
   }

      //----------------- Install & uninstall -------------------//
   public static function install(Migration $migration) {
      global $DB;

      $config = new self();

      //This class is available since version 1.3.0
      if (!$DB->tableExists("glpi_plugin_airwatch_details")) {
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
                        `is_enrolled`  tinyint(1) NOT NULL DEFAULT '0',
                        `is_compliant`  tinyint(1) NOT NULL DEFAULT '-1',
                        `is_compromised`  tinyint(1) NOT NULL DEFAULT '-1',
                        `is_dataencryption` tinyint(1) NOT NULL DEFAULT '-1',
                        `is_roaming_enabled` tinyint(1) NOT NULL DEFAULT '-1',
                        `is_data_roaming_enabled` tinyint(1) NOT NULL DEFAULT '-1',
                        `is_voice_roaming_enabled` tinyint(1) NOT NULL DEFAULT '-1',
                        PRIMARY KEY  (`id`),
                        KEY `computers_id` (`computers_id`),
                        KEY `aw_device_id` (`aw_device_id`),
                        KEY `imei` (`imei`),
                        KEY `simcard_serial` (`simcard_serial`),
                        KEY `date_last_seen` (`date_last_seen`),
                        KEY `date_last_enrollment` (`date_last_enrollment`),
                        KEY `date_last_enrollment_check` (`date_last_enrollment_check`),
                        KEY `date_last_compliance_check` (`date_last_compliance_check`),
                        KEY `date_last_compromised_check` (`date_last_compromised_check`),
                        KEY `is_enrolled` (`is_enrolled`),
                        KEY `is_compliant` (`is_compliant`),
                        KEY `is_compromised` (`is_compromised`),
                        KEY `is_dataencryption` (`is_dataencryption`),
                        KEY `is_roaming_enabled` (`is_roaming_enabled`),
                        KEY `is_data_roaming_enabled` (`is_data_roaming_enabled`),
                        KEY `is_voice_roaming_enabled` (`is_voice_roaming_enabled`)
                     ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die ($DB->error());
      } else {
         $migration->changeField('glpi_plugin_airwatch_details', 'is_compliant', 'is_compliant',
                                 'TINYINT(1) NOT NULL DEFAULT \'-1\'');
         $migration->changeField('glpi_plugin_airwatch_details', 'is_compromised', 'is_compromised',
                                 'TINYINT(1) NOT NULL DEFAULT \'-1\'');
         $migration->changeField('glpi_plugin_airwatch_details', 'is_dataencryption',
                                 'is_dataencryption', 'TINYINT(1) NOT NULL DEFAULT \'-1\'');
         $migration->changeField('glpi_plugin_airwatch_details', 'is_roaming_enabled',
                                 'is_roaming_enabled', 'TINYINT(1) NOT NULL DEFAULT \'-1\'');
         $migration->changeField('glpi_plugin_airwatch_details', 'is_data_roaming_enabled',
                                 'is_data_roaming_enabled', 'TINYINT(1) NOT NULL DEFAULT \'-1\'');
         $migration->changeField('glpi_plugin_airwatch_details', 'is_voice_roaming_enabled',
                                 'is_voice_roaming_enabled', 'TINYINT(1) NOT NULL DEFAULT \'-1\'');
         $migration->migrationOneTable('glpi_plugin_airwatch_details');
      }
   }

   public static function uninstall(Migration $migration) {
      $migration->dropTable("glpi_plugin_airwatch_details");
   }

}
