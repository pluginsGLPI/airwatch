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

class PluginAirwatchAirwatch extends CommonDBTM {

   /**
   * Cron method to export devices to XML files
   */
   static function cronairwatchImport($task) {

      //Total of export lines
      $index = 0;

      $results = PluginAirwatchRest::getDevices();
      if ($results['status'] == AIRWATCH_API_RESULT_OK
         && !empty($results['array_data']) && isset($results['array_data']['Devices'])) {
         foreach ($results['array_data']['Devices'] as $device) {
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

   /**
   * @since 0.90+1.0
   *
   * Perform an inventory for one device
   *
   * @param aw_device_id the device's Airwatch ID
   */
   static function doOneDeviceInventory($aw_device_id) {
      $results = PluginAirwatchRest::getDevice($aw_device_id);
      if ($results['status'] == AIRWATCH_API_RESULT_OK) {
         self::importDevice($results['array_data']);
      } else {
         Session::addMessageAfterRedirect(__("Error on executing the action"), true, ERROR, true);
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

      $inventory['VERSIONCLIENT'] = AIRWATCH_USER_AGENT;
      $inventory['type']          = 'Smartphone';

      $fields = array('LocationGroupName'      => 'tag',
                      'Platform'               => 'manufacturer',
                      'Model'                  => 'model',
                      'SerialNumber'           => 'serial',
                      'PhoneNumber'            => 'PHONENUMBER',
                      'LastSeen'               => 'LASTSEEN',
                      "LastEnrolledOn"         => 'LASTENROLLEDON',
                      "LastCompromisedCheckOn" => 'LASTCOMPROMISEDCHECKEDON',
                      "LastEnrollmentCheckOn"  => 'LASTENROLLMENTCHECKEDON',
                      "LastComplianceCheckOn"  => 'LASTCOMPLIANCECHECKEDON',
                      'DataEncryptionYN'       => 'DATAENCRYPTION',
                      'Imei'                   => 'IMEI',
                      'DeviceFriendlyName'     => 'name',
                      'OperatingSystem'        => 'osversion',
                      'UserName'               => 'userid',
                      'Udid'                   => 'uuid');
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

      $inventory['DEVICEID'] = $inventory['IMEI'];

      if (isset($aw_data['EnrollmentStatus'])) {
         if ($aw_data['EnrollmentStatus'] == 'Enrolled') {
            $inventory['is_enrolled'] = true;
         } else {
            $inventory['is_enrolled'] = false;
         }
      }

      if (isset($aw_data['CompromisedStatus'])) {
         if ($aw_data['CompromisedStatus'] == 'true') {
            $inventory['is_compromised'] = true;
         } else {
            $inventory['is_compromised'] = false;
         }
      }

      if (isset($aw_data['ComplianceStatus'])) {
         if ($aw_data['ComplianceStatus'] == 'Compliant') {
            $inventory['is_compliant'] = true;
         } else {
            $inventory['is_compliant'] = false;
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

      //Get applications from Airwatch
      $applications = PluginAirwatchRest::getDeviceApplications($inventory['airwatchid']);
      if ($applications['status'] == AIRWATCH_API_RESULT_OK) {
         if (isset($applications['array_data']['DeviceApps'])
            && is_array($applications['array_data']['DeviceApps'])) {
            foreach ($applications['array_data']['DeviceApps'] as $application) {
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
      }

      //Get Network informations
      $networks = PluginAirwatchRest::getDeviceNetworkInfo($inventory['airwatchid']);
      if ($networks['status'] == AIRWATCH_API_RESULT_OK) {
         $network = $networks['array_data'];
         $fields  = array('RoamingStatus', 'DataRoamingEnabled', 'VoiceRoamingEnabled');
         foreach ($fields as $field) {
            if (isset($network[$field])) {
               $inventory[strtoupper($field)] = $network[$field];
            }
         }
         //Get the wifi card mac address
         if (isset($network['WifiInfo']) && !empty($network['WifiInfo'])) {
            $inventory['wifi_macaddress'] = $network['WifiInfo']['WifiMacAddress'];
         }
         //Get the current simcard serial number
         if (isset($network['CellularNetworkInfo']['CurrentSIM'])) {
            $inventory['CURRENTSIM'] = $network['CellularNetworkInfo']['CurrentSIM'];
         }
         //Get ID addresses available
         if (isset($network['IPAddress']) && !empty($network['IPAddress'])) {
            if (isset($network['IPAddress']['WifiIPAddress'])) {
               $inventory['wifi_ipaddress'] = $network['IPAddress']['WifiIPAddress'];
            }
            if (isset($network['IPAddress']['CellularIPAddress'])) {
               $inventory['wifi_ipaddress'] = $network['IPAddress']['CellularIPAddress'];
            }
         }
      }

      //Generate an inventory XML file
      $aw_xml   = new PluginAirwatchXml($inventory);

      //Send the file to FusionInventory
      self::sendInventoryToPlugin($aw_xml->sxml);
   }

   /**
   * @since 0.90+1.0
   *
   * Send an XML inventory to FusionInventory over HTTP
   * @param confg plugin configuration
   * @param xml_data inventory in XML format
   */
   static function sendInventoryToPlugin($xml_data) {
      $config = new PluginAirwatchConfig();
      $config->getFromDB(1);

      //Do not send inventory if no service url defined
      if (!$config->getField('fusioninventory_url')) {
         return true;
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $config->getField('fusioninventory_url'));
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
      curl_setopt($ch, CURLOPT_USERAGENT, AIRWATCH_USER_AGENT);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data->asXML());
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
      curl_setopt($ch, CURLOPT_REFERER, $config->getField('fusioninventory_url'));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $ch_result = curl_exec($ch);
      curl_close($ch);
   }


   /**
   * @since 0.90+1.0
   *
   * Convert Airwatch date to GLPi DB format
   * @param airwatch date
   * @return date in Y-m-d H:i:s format
   */
   static function convertAirwatchDate($aw_date) {
      //Do not process this kind of dates coming from Airwatch : not representative
      if (preg_match("/^0001/", $aw_date)) {
         return '';
      } else {
         $date = new DateTime($aw_date);
         return date_format($date, 'Y-m-d H:i:s');
      }
   }

   /************** FusionInventory hooks ***************/

   /**
   * @since 0.90+1.0
   *
   * Add Airwatch informations coming from the XML inventory
   * @param params Airwatch section in the XML file
   */
   static function updateInventory($params = array()) {
      global $DB;

      if (!empty($params)
         && isset($params['inventory_data']) && !empty($params['inventory_data'])) {

         //Get data to be processed
         $data         = $params['inventory_data']['source'];
         $computers_id = $params['computers_id'];

         //Always delete details
         $detail = new PluginAirwatchDetail();
         $detail->deletebyCriteria(array('computers_id' => $computers_id));

         $tmp['computers_id'] = $computers_id;
         $fields = array('ROAMINGSTATUS'       => 'is_roaming_enabled',
                         'DATAROAMINGENABLED'  => 'is_data_roaming_enabled',
                         'VOICEROAMINGENABLED' => 'is_voice_roaming_enabled',
                         'CURRENTSIM'          => 'simcard_serial',
                         'IMEI'                => 'imei',
                         'PHONENUMBER'         => 'phone_number',
                         'COMPLIANCESTATUS'    => 'is_compliant',
                         'COMPROMISEDSTATUS'   => 'is_compromised',
                         'ENROLLMENTSTATUS'    => 'is_enrolled',
                         'AIRWATCHID'          => 'aw_device_id');
         foreach ($fields as $xml_field => $glpifield) {
            if (isset($data['AIRWATCH'][$xml_field])) {
               $tmp[$glpifield] = $data['AIRWATCH'][$xml_field];
            }
         }

         if (isset($data['AIRWATCH']['DATAENCRYPTION'])) {
            if ($data['AIRWATCH']['DATAENCRYPTION']) {
               $tmp['is_dataencryption'] = '1';
            } else {
               $tmp['is_dataencryption'] = '0';
            }
         }

         $dates = array('LASTSEEN'                 => 'date_last_seen',
                        'LASTENROLLEDON'           => 'date_last_enrollment',
                        'LASTENROLLMENTCHECKEDON'  => 'date_last_enrollment_check',
                        'LASTCOMPLIANCECHECKEDON'  => 'date_last_compliance_check',
                        'LASTCOMPROMISEDCHECKEDON' => 'date_last_compromised_check');
         foreach ($dates as $xmldate => $glpidate) {
            if (isset($data['AIRWATCH'][$xmldate])) {
               $tmpdate = self::convertAirwatchDate($data['AIRWATCH'][$xmldate]);
               if ($tmpdate) {
                  $tmp[$glpidate] = $tmpdate;
               }
            }
         }
         $detail->add($tmp);
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

   static function install(Migration $migration) {
      $cron = new CronTask;
      if (!$cron->getFromDBbyName(__CLASS__, 'airwatchImport')) {
         CronTask::Register(__CLASS__, 'airwatchImport', DAY_TIMESTAMP,
                            array('param' => 24, 'mode' => CronTask::MODE_EXTERNAL));
      }
   }
}
