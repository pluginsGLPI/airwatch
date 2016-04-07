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
      return array('description' => __("Import devices informations from Airwatch", "airwatch"));
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

      $inventory['VERSIONCLIENT'] = 'Airwatch-Connector-1.0';
      $inventory['type']          = 'Smartphone';

      $fields = array('LocationGroupName'  => 'tag',
                      'Platform'           => 'manufacturer',
                      'Model'              => 'model',
                      'SerialNumber'       => 'serial',
                      'PhoneNumber'        => 'PHONENUMBER',
                      'LastSeen'           => 'LASTSEEN',
                      'EnrollmentStatus'   => 'ENROLLMENTSTATUS',
                      "LastEnrolledOn"     => 'LASTENROLLEDON',
                      'ComplianceStatus'   => 'COMPLIANCESTATUS',
                      'CompromisedStatus'  => 'COMPROMISEDSTATUS',
                      'Imei'               => 'IMEI',
                      'DeviceFriendlyName' => 'name',
                      'OperatingSystem'    => 'osversion',
                      'UserName'           => 'userid',
                      'Udid'               => 'uuid');
      foreach ($fields as $aw => $fusion) {
         if (isset($aw_data[$aw])) {
            //Check if data is encodeded in utf8.
            //If not, let's encode it !
            if (!Toolbox::seems_utf8($aw_data[$aw])) {
               $aw_data[$aw] = Toolbox::encodeInUtf8($aw_data[$aw]);
            }
            $inventory[$fusion] = $aw_data[$aw];
         } else {
            $inventory[$fusion] = '';
         }
      }

      $inventory['DEVICEID']      = $inventory['IMEI'];

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

      //Get applications from Airwatch
      $applications = PluginAirwatchRest::getDeviceApplications($inventory['airwatchid']);
      if (isset($applications['DeviceApps']) && is_array($applications['DeviceApps'])) {
         foreach ($applications['DeviceApps'] as $application) {
            if (!Toolbox::seems_utf8($application['ApplicationName'])) {
               $application['ApplicationName'] = Toolbox::encodeInUtf8($application['ApplicationName']);
            }
            if (!Toolbox::seems_utf8($application['Version'])) {
               $application['Version'] = Toolbox::encodeInUtf8($application['Version']);
            }
            $inventory['applications'][] = array('name' => $application['ApplicationName'],
                                                 'version' => $application['Version']);
         }
      }

      //Generate an inventory XML file
      $aw_xml   = new PluginAirwatchXml($inventory);
      $xml_data = $aw_xml->sxml;

      //Save the file
      //$path     = '/tmp/'.$inventory['DEVICEID'].'.ocs';
      //$xml_data->asXML($path);

      //Try to set user agent
      $_SERVER['HTTP_USER_AGENT'] = $inventory['VERSIONCLIENT'];

      //Send the file to FusionInventory
     $communication = new PluginFusioninventoryCommunication();
     $communication->handleOCSCommunication($xml_data->asXML());
   }

   static function updateInventory($params = array()) {
      global $DB;

      if (!empty($params)
         && isset($params['inventory_data']) && !empty($params['inventory_data'])) {

         //Get data to be processed
         $data         = $params['inventory_data']['source'];
         $computers_id = $params['computers_id'];

         $detail = new PluginAirwatchDetail();

         $tmp                      = array();
         $tmp['computers_id']      = $computers_id;
         $tmp['imei']              = $data['AIRWATCH']['IMEI'];
         $tmp['phone_number']      = $data['AIRWATCH']['PHONENUMBER'];
         $tmp['compliancestatus']  = $data['AIRWATCH']['COMPLIANCESTATUS'];
         $tmp['compromisedstatus'] = $data['AIRWATCH']['COMPROMISEDSTATUS'];
         $tmp['enrollmentstatus']  = $data['AIRWATCH']['ENROLLMENTSTATUS'];

         $sql_condition = "`computers_id`='$computers_id'";
         //Are there any airwatch data for a device. Use imei as unique identifier
         if (!countElementsInTable('glpi_plugin_airwatch_details',
                                   $sql_condition)) {
            $detail->add($tmp);
         } else {
            $dbdetail  = getAllDatasFromTable('glpi_plugin_airwatch_details', $sql_condition);
            $mydetail  = array_pop($dbdetail);
            $tmp['id'] = $mydetail['id'];
            $detail->update($tmp);
         }
      }
   }

   static function addInventoryInfos($params = array()) {
      $values = array();

      if (isset($params['source'])
         && is_array($params['source'])
            && !empty($params['source']) && isset($params['source']['imei'])) {
         //Add airwatch info to the list of data to be processed
         foreach ($params['source'] as $field => $value) {
            $values['AIRWATCH'][$field] = $value;
         }
      }
      return $values;
   }
}
