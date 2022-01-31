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

class PluginAirwatchRest {

   /**
   * @since 0.90+1.0
   *
   * Test API by calling the help page
   *
   * @return an array which contains:
   *         - an execution status code : OK or KO
   *         - the error message if execution fails
   *         - data if execution is a success
   */
   static function testConnection() {
      return self::callApi('/help');
   }

   /**
   * @since 0.90+1.0
   *
   * Call Airwatch REST API
   *
   * @param endpoint endpoint to call
   * @return an array which contains:
   *         - an execution status code : OK or KO
   *         - the error message if execution fails
   *         - data if execution is a success
   */
   static function callApi($endpoint) {

      //Array to return API call informations
      $result = [];

      //Get airwatch access configuration
      $config = new PluginAirwatchConfig();
      $config->getFromDB(1);

      if (!isset($config->fields['airwatch_service_url'])
         || $config->fields['airwatch_service_url'] == '') {
            return false;
      }

      //Encode auth informations in base64
      $basic_auth = base64_encode($config->fields['username'].':'.$config->fields['password']);
      $ch = curl_init();

      //Build full endpoit URL
      $url = $config->fields['airwatch_service_url'].$endpoint;
      curl_setopt($ch, CURLOPT_URL, $url);

      $headers = ['aw-tenant-code: '.$config->fields['api_key'],
                  'Authorization: Basic '.$basic_auth,
                  'Accept: application/json'];
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
      //Get curl informations about the last call
      $infos = curl_getinfo($ch);

      //If http_code is not 200, then there's an error
      if ($infos['http_code'] != 200) {
         $result['status'] = AIRWATCH_API_RESULT_ERROR;
         $result['error']  = $infos['http_code'];
      } else {
         $result['status'] = AIRWATCH_API_RESULT_OK;
         $result['data'] = $ch_result;
      }
      curl_close($ch);

      return $result;
   }

   /**
   * @since 0.90+1.1
   *
   * Get profies for a device using the Airwatch rest API
   * @param the Airwatch Device ID
   * @return profiles informations as an array
   */
   static function getDeviceProfiles($device_id) {
      return self::callApiAndGetData('/mdm/devices/'.$device_id.'/profiles');
   }

   /**
   * @since 0.90+1.1
   *
   * Get profile compliance for a device using the Airwatch rest API
   * @param the Airwatch Device ID
   * @return compliance informations as an array
   */
   static function getDeviceCompliance($device_id) {
      return self::callApiAndGetData('/mdm/devices/'.$device_id.'/compliance');
   }

   /**
   * @since 0.90+1.0
   *
   * Get all devices using the Airwatch rest API
   * @return devices informations as an array
   */
   static function getDevices() {
      return self::callApiAndGetData('/mdm/devices/search');
   }

   /**
   * @since 0.90+1.0
   *
   * Get a device informations using the Airwatch rest API
   * @param the Airwatch Device ID
   * @return device informations as an array
   */
   static function getDevice($device_id) {
      return self::callApiAndGetData('/mdm/devices/'.$device_id);
   }

   /**
   * @since 0.90+1.0
   *
   * Get a device network informations using the Airwatch rest API
   * @param the Airwatch Device ID
   * @return network informations as an array
   */
   static function getDeviceNetworkInfo($device_id) {
      return self::callApiAndGetData('/mdm/devices/'.$device_id.'/network');
   }

   /**
   * @since 0.90+1.0
   *
   * Get a device applications using the Airwatch rest API
   * @param the Airwatch Device ID
   * @return applications as an array
   */
   static function getDeviceApplications($device_id) {
      return self::callApiAndGetData('/mdm/devices/'.$device_id.'/apps');
   }

   /**
   * @since 0.90+1.0
   *
   * Get details for one device using the Airwatch rest API
   * @return the device informations as an array
   */
   static function callApiAndGetData($endpoint) {

      $results = self::callApi($endpoint);
      if ($results['status'] == AIRWATCH_API_RESULT_OK
          && isset($results['data']) && !empty($results['data'])) {
         $data = json_decode($results['data'], true);
         $results['array_data'] = $data;
      } else {
         $results['array_data'] = [];
      }
      return $results;
   }

}
