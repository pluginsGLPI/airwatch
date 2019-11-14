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

class PluginAirwatchCompliance extends CommonDBTM {

   //Do not record historical, because details are deleted and recreated at each inventory
   public $dohistory       = false;

   /**
   * since 0.90+1.1
   *
   * Delete all profiles informations for a device
   * @param computers_id GLPi device ID
   */
   static function deleteForComputer($computers_id) {
      global $DB;

      $query = "DELETE FROM `glpi_plugin_airwatch_compliances`
                WHERE `computers_id`='$computers_id'";
      $DB->query($query);
   }

   static function showForComputer(CommonDBTM $item) {
      $computers_id = $item->getID();
      $data = getAllDataFromTable(
         'glpi_plugin_airwatch_compliances',
         ['computers_id' => $computers_id]
      );
      if (empty($data)) {
         return true;
      }

      echo '<tr>';
      echo '<th colspan="4">'.__('Compliance details', 'airwatch').'</th>';
      echo '</tr>';

      foreach ($data as $compliance) {
         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>";
         echo $compliance['name'];
         echo "</td><td>";
         echo PluginAirwatchDetail::showYesNoNotSet($compliance['is_compliant'], true);
         echo "</td>";
         echo "<td>";
         echo __("Last check date", "airwatch"). "</td><td>";
         echo PluginAirwatchDetail::getHumanReadableDate($_SESSION['glpi_currenttime'],
                                                         $compliance['date_last_check'],
                                                         1);
         echo "</td>";
         echo '</tr>';
      }

   }
   /**
   * since 0.90+1.1
   *
   * Add a device compliance
   * @param computers_id GLPi device ID
   * @param name profile name
   * @param is_compliant compliance status
   * @param date_last_check last compliance check date
   *
   * @return the compliance ID
   */
   static function addProfile($computers_id, $name, $is_compliant, $date_last_check) {
      $compliance = new self();
      return $compliance->add(['computers_id'    => $computers_id,
                               'name'            => $name,
                               'is_compliant'    => ($is_compliant?'1':'0'),
                               'date_last_check' => $date_last_check]);
   }

   static function install(Migration $migration) {
      global $DB;

      if (!$DB->tableExists('glpi_plugin_airwatch_compliances')) {
         $query = "CREATE TABLE `glpi_plugin_airwatch_compliances` (
           `id` int(11) NOT NULL AUTO_INCREMENT,
           `computers_id` int(11) NOT NULL DEFAULT '0',
           `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
           `is_compliant` tinyint(1) NOT NULL DEFAULT '0',
           `date_last_check` datetime DEFAULT NULL,
           PRIMARY KEY (`id`),
           KEY `computers_id` (`computers_id`),
           KEY `is_compliant` (`is_compliant`),
           KEY `date_last_check` (`date_last_check`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, $DB->error());
      }
   }

   static function uninstall(Migration $migration) {
      $migration->dropTable("glpi_plugin_airwatch_compliances");
   }
}
