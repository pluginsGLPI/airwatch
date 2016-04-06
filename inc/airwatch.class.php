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
r
 ---------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class PluginAirwatchAirwatch extends CommonDBTM {

   /**
   * Cron method to export devices to XML files
   */
   static function cronairwatchImport($task) {

      //Total of export lines
      $index = 0;

      $devices = PluginAirwatchRest::getDevices();
      if (!empty($devices) && isset($devices['Devices'])) {
         foreach ($devices['Devices'] as $device) {
            if (empty($device)) {
               continue;
            }
            self::importDevice($device);
            $index++;
         }
      }
      $task->addVolume($index);
      return true;
   }

   static function cronInfo($name) {
      return array('description' => __("Airwatch connector", "airwatch"));
   }

   static function install(Migration $migration) {
      $cron = new CronTask;
      if (!$cron->getFromDBbyName(__CLASS__, 'airwatchImport')) {
         CronTask::Register(__CLASS__, 'airwatchImport', DAY_TIMESTAMP,
                            array('param' => 24, 'mode' => CronTask::MODE_EXTERNAL));
      }
   }

   /**
   * @since 0.90+1.0
   *
   * Build the inventory and send it to FusionInventory for import and update
   * @param aw_data device infomations, as sent by the REST API
   * @return
   */
   static function importDevice($aw_data = array()) {
      //Array to store device inventory, as requested by FusionInventory
      $inventory = array();

      //ACCOUNTINFO section
      //Use LocationGroupName as TAG
      if (isset($aw_data['LocationGroupName'])) {
         $inventory['ACCOUNTINFO'][] = array('KEYNAME' => 'TAG',
                                             'KEYVALUE' => $aw_data['LocationGroupName']);
      }

      if (isset($aw_data['Platform'])) {
         $inventory['BIOS']['MMANUFACTURER'] = $aw_data['Platform'];
      }

      if (isset($aw_data['Model'])) {
         $inventory['BIOS']['SMODEL'] = $aw_data['Model'];
      }

      //BIOS section
      if (isset($aw_data['Serial'])) {
         $inventory['BIOS']['SSN'] = $aw_data['Serial'];
      }

      //HARDWARE section
      if (isset($aw_data['DeviceFriendlyName'])) {
         $inventory['HARDWARE']['NAME'] = $aw_data['DeviceFriendlyName'];
      }

      if (isset($aw_data['OperatingSystem'])) {
         switch ($aw_data['Platform']) {
            case 'Apple':
               $inventory['HARDWARE']['OSNAME'] = 'iOS';
               break;
            case 'Android':
               $inventory['HARDWARE']['OSNAME'] = 'Android';
               break;

         }

         $inventory['HARDWARE']['OSVERSION'] = $aw_data['OperatingSystem'];
      }

      if (isset($aw_data['UserName'])) {
         $inventory['HARDWARE']['USERID'] = $aw_data['UserName'];
      }

      //NETWORK section
      if (isset($aw_data['MacAddress'])) {
         $inventory['NETWORKS'][] = array('MACADDR' => $aw_data['MacAddress']);
      }


      $fields = array('Id' => 'AIRWATCHID', 'PhoneNumber' => 'PHONENUMBER', 'LastSeen' => 'LASTCONTACT', 
                      'EnrollmentStatus' => 'ENROLLMENTSATUS',  "LastEnrolledOn" => 'LASTENROLLEDON',
                      'ComplianceStatus' => 'COMPLIANCESTATUS', 'CompromisedStatus' => 'COMPRIMISEDSTATUS');
      foreach ($fields as $aw => $fusion) {
         if (isset($aw_data[$aw])) {
            $inventory['AIRWATCH'][$fusion] = $aw_data[$aw]; 
         }
      }
      Toolbox::logDebug($inventory);
   }
}
