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
