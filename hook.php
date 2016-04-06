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

function plugin_airwatch_install() {
   $migration = new Migration("0.90+1.0");
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
?>
