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

 function plugin_airwatch_getAddSearchOptions($itemtype) {

    $sopt = array();
    if ($itemtype == 'Computer') {
          $sopt[6000]['table']         = 'glpi_plugin_airwatch_details';
          $sopt[6000]['field']         = 'aw_device_id';
          $sopt[6000]['name']          = __('Airwatch', 'airwatch').'-'.__('Airwatch ID', 'airwatch');
          $sopt[6000]['datatype']      = 'integer';
          $sopt[6000]['joinparams']    = array('jointype' => 'child');
          $sopt[6000]['massiveaction'] = FALSE;

          $sopt[6001]['table']         = 'glpi_plugin_airwatch_details';
          $sopt[6001]['field']         = 'imei';
          $sopt[6001]['name']          = __('Airwatch', 'airwatch').'-'.__('IMEI', 'airwatch');
          $sopt[6001]['datatype']      = 'integer';
          $sopt[6001]['joinparams']    = array('jointype' => 'child');
          $sopt[6001]['massiveaction'] = FALSE;

         $sopt[6002]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6002]['field']         = 'simcard_serial';
         $sopt[6002]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Simcard serial number', 'airwatch');
         $sopt[6002]['datatype']      = 'integer';
         $sopt[6002]['joinparams']    = array('jointype' => 'child');
         $sopt[6002]['massiveaction'] = FALSE;

         $sopt[6003]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6003]['field']         = 'date_last_seen';
         $sopt[6003]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Last seen', 'airwatch');
         $sopt[6003]['datatype']      = 'datetime';
         $sopt[6003]['joinparams']    = array('jointype' => 'child');
         $sopt[6003]['massiveaction'] = FALSE;

         $sopt[6004]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6004]['field']         = 'date_last_enrollment';
         $sopt[6004]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Last enrollment date', 'airwatch');
         $sopt[6004]['datatype']      = 'datetime';
         $sopt[6004]['joinparams']    = array('jointype' => 'child');
         $sopt[6004]['massiveaction'] = FALSE;

         $sopt[6005]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6005]['field']         = 'date_last_enrollment_check';
         $sopt[6005]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Last enrollment check date', 'airwatch');
         $sopt[6005]['datatype']      = 'datetime';
         $sopt[6005]['joinparams']    = array('jointype' => 'child');
         $sopt[6005]['massiveaction'] = FALSE;

         $sopt[6006]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6006]['field']         = 'date_last_compliance_check';
         $sopt[6006]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Last compliance check date', 'airwatch');
         $sopt[6006]['datatype']      = 'datetime';
         $sopt[6006]['joinparams']    = array('jointype' => 'child');
         $sopt[6006]['massiveaction'] = FALSE;

         $sopt[6007]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6007]['field']         = 'date_last_compromised_check';
         $sopt[6007]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Last compromised check date', 'airwatch');
         $sopt[6007]['datatype']      = 'datetime';
         $sopt[6007]['joinparams']    = array('jointype' => 'child');
         $sopt[6007]['massiveaction'] = FALSE;

         $sopt[6008]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6008]['field']         = 'enrollmentstatus';
         $sopt[6008]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Enrollment status', 'airwatch');
         $sopt[6008]['datatype']      = 'string';
         $sopt[6008]['joinparams']    = array('jointype' => 'child');
         $sopt[6008]['massiveaction'] = FALSE;

         $sopt[6009]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6009]['field']         = 'compliancestatus';
         $sopt[6009]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Compliance status', 'airwatch');
         $sopt[6009]['datatype']      = 'string';
         $sopt[6009]['joinparams']    = array('jointype' => 'child');
         $sopt[6009]['massiveaction'] = FALSE;

         $sopt[6010]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6010]['field']         = 'compromisedstatus';
         $sopt[6010]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Compromised status', 'airwatch');
         $sopt[6010]['datatype']      = 'string';
         $sopt[6010]['joinparams']    = array('jointype' => 'child');
         $sopt[6010]['massiveaction'] = FALSE;

         $sopt[6011]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6011]['field']         = 'is_dataencryption';
         $sopt[6011]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Data encryption', 'airwatch');
         $sopt[6011]['datatype']      = 'bool';
         $sopt[6011]['joinparams']    = array('jointype' => 'child');
         $sopt[6011]['massiveaction'] = FALSE;
         $sopt[6011]['searchtype']     = array('equals', 'notequals');

         $sopt[6012]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6012]['field']         = 'is_roaming_enabled';
         $sopt[6012]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Roaming enabled', 'airwatch');
         $sopt[6012]['datatype']      = 'bool';
         $sopt[6012]['joinparams']    = array('jointype' => 'child');
         $sopt[6012]['massiveaction'] = FALSE;
         $sopt[6012]['searchtype']     = array('equals', 'notequals');

         $sopt[6013]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6013]['field']         = 'is_data_roaming_enabled';
         $sopt[6013]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Data roaming enabled', 'airwatch');
         $sopt[6013]['datatype']      = 'bool';
         $sopt[6013]['joinparams']    = array('jointype' => 'child');
         $sopt[6013]['massiveaction'] = FALSE;
         $sopt[6013]['searchtype']     = array('equals', 'notequals');

         $sopt[6014]['table']         = 'glpi_plugin_airwatch_details';
         $sopt[6014]['field']         = 'is_voice_roaming_enabled';
         $sopt[6014]['name']          = __('Airwatch', 'airwatch').'-'.
                                          __('Voice roaming enabled', 'airwatch');
         $sopt[6014]['datatype']      = 'bool';
         $sopt[6014]['joinparams']    = array('jointype' => 'child');
         $sopt[6014]['massiveaction'] = FALSE;
         $sopt[6014]['searchtype']     = array('equals', 'notequals');

       }

   return $sopt;
}

/***************** Install / uninstall functions **************/

function plugin_airwatch_install() {
   $migration = new Migration(AIRWATCH_PLUGIN_VERSION);
   include (GLPI_ROOT."/plugins/airwatch/inc/config.class.php");
   include (GLPI_ROOT."/plugins/airwatch/inc/airwatch.class.php");
   include (GLPI_ROOT."/plugins/airwatch/inc/detail.class.php");
   PluginairwatchConfig::install($migration);
   PluginAirwatchAirwatch::install($migration);
   PluginAirwatchDetail::install($migration);
   return true;
}

function plugin_airwatch_uninstall() {
   include (GLPI_ROOT."/plugins/airwatch/inc/config.class.php");
   include (GLPI_ROOT."/plugins/airwatch/inc/detail.class.php");
   PluginairwatchConfig::uninstall();
   PluginairwatchDetail::uninstall();
   return true;
}
