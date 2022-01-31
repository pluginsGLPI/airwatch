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

class PluginAirwatchAirwatch extends CommonDBTM {

   //Do not record historical, because details are deleted and recreated at each inventory
   public $dohistory       = false;

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
      return ['description' => __("Import devices informations from Airwatch", "airwatch")];
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
   static function importDevice($aw_data = []) {
      //Array to store device inventory, as requested by FusionInventory
      $inventory = [];

      $inventory['VERSIONCLIENT'] = AIRWATCH_USER_AGENT;
      $inventory['type']          = 'Smartphone';

      $fields = ['LocationGroupName'      => 'tag',
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
                 'Udid'                   => 'uuid'];
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
            $inventory['ISENROLLED'] = 1;
         } else {
            $inventory['ISENROLLED'] = 0;
         }
      }

      if (isset($aw_data['CompromisedStatus'])) {
         if ($aw_data['CompromisedStatus'] == 'true') {
            $inventory['ISCOMPROMISED'] = 1;
         } else {
            $inventory['ISCOMPROMISED'] = 0;
         }
      }

      if (isset($aw_data['ComplianceStatus'])) {
         if ($aw_data['ComplianceStatus'] == 'Compliant') {
            $inventory['ISCOMPLIANT'] = 1;
         } else {
            $inventory['ISCOMPLIANT'] = 0;
         }
      }

      if (isset($aw_data['OperatingSystem'])) {
         switch ($aw_data['Platform']) {
            case 'Apple':
               $inventory['osname'] = 'iOS';
               break;
            case 'Android':
               $inventory['osname'] = 'Android';
               if (preg_match('/^(.*) (.*)$/', $aw_data['Model'], $results)) {
                  $inventory['manufacturer']  = $results[1];
                  $inventory['model']         = $results[2];
               }
               break;

         }
      }

      if (is_array($aw_data['Id'])) {
         $inventory['airwatchid'] = $aw_data['Id']['Value'];
      }

      $inventory['applications'] = [];

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
               $inventory['applications'][] = ['name' => $application['ApplicationName'],
                                               'version' => $application['Version']];
            }
         }
      }

      //Get Network informations
      $networks = PluginAirwatchRest::getDeviceNetworkInfo($inventory['airwatchid']);
      if ($networks['status'] == AIRWATCH_API_RESULT_OK) {
         $network = $networks['array_data'];
         $fields  = ['RoamingStatus', 'DataRoamingEnabled', 'VoiceRoamingEnabled'];
         foreach ($fields as $field) {
            if (isset($network[$field])) {
               $inventory[strtoupper($field)] = $network[$field];
            }
         }
         //Get the wifi card mac address
         if (isset($network['WifiInfo'])
            && !empty($network['WifiInfo'])
               && isset($network['WifiInfo']['WifiMacAddress'])) {
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

      //Get Network informations
      $compliances = PluginAirwatchRest::getDeviceCompliance($inventory['airwatchid']);
      if ($compliances['status'] == AIRWATCH_API_RESULT_OK) {
         if (isset($compliances['array_data']['DeviceCompliance'])
            && is_array($compliances['array_data']['DeviceCompliance'])) {
            foreach ($compliances['array_data']['DeviceCompliance'] as $compliance) {
               $inventory['AIRWATCHCOMPLIANCE'][] = $compliance;
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
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
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
   static function updateInventory($params = []) {
      //Toolbox::logDebug("updateInventory", $params);
      global $DB;

      if (!empty($params)
         && isset($params['inventory_data']) && !empty($params['inventory_data'])) {

         //Get data to be processed
         $data         = $params['inventory_data'];
         $computers_id = $params['computers_id'];

         //Always delete details
         $detail = new PluginAirwatchDetail();
         $detail->deletebyCriteria(['computers_id' => $computers_id]);

         //Delete airwatch profiles
         PluginAirwatchCompliance::deleteForComputer($computers_id);

         $tmp['computers_id'] = $computers_id;
         $fields = ['ROAMINGSTATUS'       => 'is_roaming_enabled',
                    'DATAROAMINGENABLED'  => 'is_data_roaming_enabled',
                    'VOICEROAMINGENABLED' => 'is_voice_roaming_enabled',
                    'CURRENTSIM'          => 'simcard_serial',
                    'IMEI'                => 'imei',
                    'PHONENUMBER'         => 'phone_number',
                    'ISCOMPLIANT'         => 'is_compliant',
                    'ISCOMPROMISED'       => 'is_compromised',
                    'ISENROLLED'          => 'is_enrolled',
                    'AIRWATCHID'          => 'aw_device_id'];
         foreach ($fields as $xml_field => $glpifield) {
            if (isset($data['AIRWATCH'][$xml_field]) && $data['AIRWATCH'][$xml_field]) {
               $tmp[$glpifield] = $data['AIRWATCH'][$xml_field];
            } else {
               if (preg_match('/is_/', $glpifield)) {
                  $tmp[$glpifield] = '-1';
               } else {
                  $tmp[$glpifield] = '';
               }
            }
         }

         if (isset($data['AIRWATCH']['DATAENCRYPTION'])) {
            if ($data['AIRWATCH']['DATAENCRYPTION'] == 'Y') {
               $tmp['is_dataencryption'] = '1';
            } else {
               $tmp['is_dataencryption'] = '0';
            }
         }
         $dates = ['LASTSEEN'                 => 'date_last_seen',
                   'LASTENROLLEDON'           => 'date_last_enrollment',
                   'LASTENROLLMENTCHECKEDON'  => 'date_last_enrollment_check',
                   'LASTCOMPLIANCECHECKEDON'  => 'date_last_compliance_check',
                   'LASTCOMPROMISEDCHECKEDON' => 'date_last_compromised_check'];
         foreach ($dates as $xmldate => $glpidate) {
            if (isset($data['AIRWATCH'][$xmldate])) {
               $tmpdate = self::convertAirwatchDate($data['AIRWATCH'][$xmldate]);
               if ($tmpdate) {
                  $tmp[$glpidate] = $tmpdate;
               }
            }
         }
         $detail->add($tmp);

         if (isset($data['AIRWATCHCOMPLIANCE'])) {
            $compliances = [];
            $go          = true;

            if (isset($data['AIRWATCHCOMPLIANCE']['NAME'])) {
               $compliances = [$data['AIRWATCHCOMPLIANCE']];
            } else if (is_array($data['AIRWATCHCOMPLIANCE'])) {
               $compliances = $data['AIRWATCHCOMPLIANCE'];
            } else {
               $go = false;
            }

            if ($go) {
               foreach ($compliances as $compliance) {
                  $tmpdate = self::convertAirwatchDate($compliance['LASTCHECK']);

                  PluginAirwatchCompliance::addProfile($computers_id,
                                                       $compliance['NAME'],
                                                       $compliance['COMPLIANCESTATUS'],
                                                       $tmpdate);
               }
            }
         }
      }
   }

   static function addInventoryInfos($params = []) {
      $values = [];
      if (isset($params['source'])
         && isset($params['source']['AIRWATCH'])
            && is_array($params['source']['AIRWATCH'])
               && !empty($params['source']['AIRWATCH'])) {
         //Add airwatch info to the list of data to be processed
         foreach ($params['source']['AIRWATCH'] as $field => $value) {
            $values['AIRWATCH'][$field] = $value;
         }
         if (isset($params['source']['AIRWATCHCOMPLIANCE'])) {
            foreach ($params['source']['AIRWATCHCOMPLIANCE'] as $field => $value) {
               $values['AIRWATCHCOMPLIANCE'][$field] = $value;
            }
         }
      }
      return $values;
   }

   static function install(Migration $migration) {
      $cron = new CronTask;
      if (!$cron->getFromDBbyName(__CLASS__, 'airwatchImport')) {
         CronTask::Register(__CLASS__, 'airwatchImport', DAY_TIMESTAMP,
                            ['param' => 24, 'mode' => CronTask::MODE_EXTERNAL]);
      }
   }
}
