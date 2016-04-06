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

class PluginAirwatchRest {

   static function testConnection() {
      return self::callApi('/help');
   }

   static function callApi($endpoint) {

      //Array to return API call informations
      $result = array();

      //Get airwatch access configuration
      $config = new PluginAirwatchConfig();
      $config->getFromDB(1);

      //Encode auth informations in base64
      $basic_auth = base64_encode($config->fields['username'].':'.$config->fields['password']);
      $ch = curl_init();

      //Build full endpoit URL
      $url = $config->fields['airwatch_service_url'].$endpoint;
      curl_setopt($ch, CURLOPT_URL, $url);

      $headers = array('aw-tenant-code: '.$config->fields['api_key'],
                       'Authorization: Basic '.$basic_auth,
                       'Accept: application/json');
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_VERBOSE, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      //Skip SSL check if requested in the plugin's configuration
      if ($config->fields['skip_ssl_check']) {
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      }

      $ch_result = curl_exec($ch);
      if (!$ch_result) {
         $results['status'] = AIRWATCH_API_RESULT_ERROR;
         $results['error']  = curl_error($ch);
      } else {
         $result['status'] = AIRWATCH_API_RESULT_OK;
         $result['data'] = $ch_result;
      }
      curl_close($ch);

      return $result;
   }

   /**
   * @since 0.90+1.0
   *
   * Get all devices using the Airwatch rest API
   * @return devices informations as an array
   */
   static function getDevices() {

      $results = self::callApi('/mdm/devices/search');
      if ($results['status'] == AIRWATCH_API_RESULT_OK 
          && isset($results['data']) && !empty($results['data'])) {
         $data = json_decode($results['data'], true);
      } else {
         $data = array();
      }
      return $data;
   }

   /**
   * @since 0.90+1.0
   *
   * Get details for one device using the Airwatch rest API
   * @return the device informations as an array
   */
   static function getDeviceByID($aw_device_id) {

      $results = self::callApi('/mdm/devices/$aw_device_id');
      $data = json_decode($results, true);
      return $data;
   }

}
