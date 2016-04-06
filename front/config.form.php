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

include ("../../../inc/includes.php");

$config = new PluginAirwatchConfig();

if (isset($_POST['test'])) {
   $result = PluginAirwatchRest::testConnection();
   if ($result['status'] == AIRWATCH_API_RESULT_OK) {
      Session::addMessageAfterRedirect("Connection successful", true, INFO, true);
   } else {
      $message = "Connection error: ".$result['message'];
      Session::addMessageAfterRedirect($message, true, ERROR, true);
   }
   Html::back();
}
if (isset($_POST["update"])) {
   $config->update($_POST);
   Html::back();
} else {

Html::header(__("Airwatch", "airwatch"), $_SERVER['PHP_SELF'], "plugins", "airwatch",
             "config");

Session::checkRight("config", UPDATE);
$config->showForm();

Html::footer();

}
