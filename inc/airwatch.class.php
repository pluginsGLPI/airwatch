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

      $inventory['DEVICEID']      = $aw_data['Imei'];
      $inventory['VERSIONCLIENT'] = 'Airwatch-Connector-1.0';
      $inventory['type'] = 'Smartphone';

      $fields = array('LocationGroupName' => 'tag',
                      'Platform' => 'manufacturer',
                      'Model' => 'model',
                      'SerialNumber' => 'serial',
                      'PhoneNumber' => 'phonenumber',
                      'LastSeen' => 'lastcontact',
                      'EnrollmentStatus' => 'enrollmentstatus',
                      "LastEnrolledOn" => 'LASTENROLLEDON',
                      'ComplianceStatus' => 'COMPLIANCESTATUS',
                      'CompromisedStatus' => 'COMPRIMISEDSTATUS',
                      'Imei' => 'imei',
                      'DeviceFriendlyName' => 'name',
                      'OperatingSystem' => 'osversion',
                      'UserName' => 'userid',
                      'Udid' => 'uuid');
      foreach ($fields as $aw => $fusion) {
         if (isset($aw_data[$aw])) {
            $inventory[$fusion] = $aw_data[$aw];
         } else {
            $inventory[$fusion] = '';
         }
      }

      if (isset($aw_data['OperatingSystem'])) {
         switch ($aw_data['Platform']) {
            case 'Apple':
               $inventory['osname'] = 'iOS';
               break;
            case 'Android':
               $inventory['osname'] = 'Android';
               break;

         }
      }
  
      if (is_array($aw_data['Id'])) {
         $inventory['airwatchid'] = $aw_data['Id']['Value'];
      }

      $inventory['applications'] = array();
      $applications = PluginAirwatchRest::getDeviceApplications($inventory['airwatchid']);
      if (isset($applications['DeviceApps']) && is_array($applications['DeviceApps'])) {
         foreach ($applications['DeviceApps'] as $application) { 
            $inventory['applications'][] = array('name' => $application['ApplicationName'], 
                                                 'version' => $application['Version']);
         }
      }
      //Generate an inventory XML file
      $aw_xml   = new PluginAirwatchXml($inventory);
      $xml_data = $aw_xml->sxml;

      //Save the file
      $path     = '/tmp/'.$inventory['DEVICEID'].'.ocs';
      $xml_data->asXML($path);

      //Send the file to FusionInventory
      //$communication = new PluginFusioninventoryCommunication();
      //$result = $communication->handleOCSCommunication($xml_data);
      //Toolbox::logDebug($result);
   }
}
