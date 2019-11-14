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

define ('AIRWATCH_API_RESULT_OK', 'ok');
define ('AIRWATCH_API_RESULT_ERROR', 'ko');
define ('AIRWATCH_USER_AGENT', 'Airwatch-Connector-1.1');
define ('PLUGIN_AIRWATCH_VERSION', '1.4.1');

// Minimal GLPI version, inclusive
define('PLUGIN_AIRWATCH_MIN_GLPI', '9.2');
// Maximum GLPI version, exclusive
define('PLUGIN_AIRWATCH_MAX_GLPI', '9.6');

function plugin_init_airwatch() {
   global $PLUGIN_HOOKS,$CFG_GLPI,$LANG;
   $PLUGIN_HOOKS['csrf_compliant']['airwatch'] = true;

   $plugin = new Plugin();
   if ($plugin->isActivated('airwatch')) {

      Plugin::registerClass('PluginAirwatchDetail', ['addtabon' => ['Computer']]);

      $PLUGIN_HOOKS['use_massive_action']['airwatch'] = 1;

      $PLUGIN_HOOKS['config_page']['airwatch'] = 'front/config.form.php';
      $PLUGIN_HOOKS['item_purge']['order']  = [
            'Computer' => ['PluginAirwatchDetail', 'cleanOnPurge']];
      $PLUGIN_HOOKS['import_item']['airwatch']
         = ['Computer' => ['Plugin']];
      $PLUGIN_HOOKS['autoinventory_information']['airwatch']
         = ['Computer' =>  ['PluginAirwatchDetail', 'showInfo']];

      //FusionInventory hooks
      $PLUGIN_HOOKS['fusioninventory_inventory']['airwatch']
         = ['PluginAirwatchAirwatch', 'updateInventory'];
      $PLUGIN_HOOKS['fusioninventory_addinventoryinfos']['airwatch']
         = ['PluginAirwatchAirwatch', 'addInventoryInfos'];
   }
}

function plugin_version_airwatch() {
   return [
      'name'           => __("GLPi Airwatch Connector", 'airwatch'),
      'version'        => PLUGIN_AIRWATCH_VERSION,
      'author'         => "<a href='http://www.teclib-edition.com'>Teclib'</a>",
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/pluginsglpi/airwatch',
      'requirements'   => [
         'glpi' => [
            'min'     => PLUGIN_AIRWATCH_MIN_GLPI,
            'max'     => PLUGIN_AIRWATCH_MAX_GLPI,
            'plugins' => [
               'fusioninventory',
            ],
         ],
         'php' => [
            'exts' => [
               'curl' => [
                  'required' => true,
               ]
            ]
         ]
      ]
   ];
}

function plugin_airwatch_check_prerequisites() {

   //Requirements check is not done by core in GLPI < 9.2 but has to be delegated to core in GLPI >= 9.2.
   if (!method_exists('Plugin', 'checkGlpiVersion')) {
      $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
      $matchMinGlpiReq = version_compare($version, PLUGIN_AIRWATCH_MIN_GLPI, '>=');
      $matchMaxGlpiReq = version_compare($version, PLUGIN_AIRWATCH_MAX_GLPI, '<');

      if (!$matchMinGlpiReq || !$matchMaxGlpiReq) {
         echo vsprintf(
            'This plugin requires GLPI >= %1$s and < %2$s.',
            [
               PLUGIN_AIRWATCH_MIN_GLPI,
               PLUGIN_AIRWATCH_MAX_GLPI,
            ]
         );
         return false;
      }

      if (!function_exists('curl_init')) {
         echo "cURL extension (PHP) is required.";
         return false;
      }

      $plugin = new Plugin();
      if (!$plugin->isActivated('fusioninventory')) {
         echo "Fusioninventory plugin must be enabled";
         return false;
      }
   }

   return true;
}

function plugin_airwatch_check_config() {

   return true;
}
