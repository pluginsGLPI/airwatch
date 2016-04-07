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
      public $dohistory       = true;


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


      /**
       * @see CommonGLPI::defineTabs()
       *
       * @since version 0.85
      **/
      function defineTabs($options=array()) {

         $ong = array();
         $this->addDefaultFormTab($ong);
         $this->addStandardTab('Log', $ong, $options);

         return $ong;
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

         echo "<tr><th colspan='4'>" . __("Airwatch informations", "airwatch") . "</th></tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Imei", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['imei'];
         echo "</td>";

         echo "<td>" . __("Phone number", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['phone_number'];
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Enrollment status", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['enrollmentstatus'];
         echo "</td>";
         echo "<td>" . __("Compliance status", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['compliancestatus'];
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td>" . __("Comprimsed status", "airwatch") . "</td>";
         echo "<td>";
         echo $detail->fields['compromisedstatus'];
         echo "</td>";
         echo "<td>" . __("Last seen", "airwatch") . "</td>";
         echo "<td>";
         echo Html::convDateTime($detail->fields['last_seen']);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1' align='center'>";
         echo "<td colspan='4' align='center'>";
         echo "<input type='submit' name='update' value=\"" . _sx("button", "Update") . "\" class='submit' >";
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
                        `computers_id` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                        `imei` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                        `phone_number` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                        `date_mod` datetime DEFAULT NULL,
                        `date_creation` datetime DEFAULT NULL,
                        `last_contact` datetime DEFAULT NULL,
                        `last_seen` datetime DEFAULT NULL,
                        `enrollmentstatus` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                        `compliancestatus` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
                        `compromisedstatus` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
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
