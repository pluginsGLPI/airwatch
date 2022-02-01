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

define ('AIRWATCH_API_RESULT_OK', 'ok');
define ('AIRWATCH_API_RESULT_ERROR', 'ko');
define ('AIRWATCH_USER_AGENT', 'Airwatch-Connector-1.1');
define ('PLUGIN_AIRWATCH_VERSION', '1.5.0');

// Minimal GLPI version, inclusive
define('PLUGIN_AIRWATCH_MIN_GLPI', '10.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_AIRWATCH_MAX_GLPI', '10.0.99');

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
