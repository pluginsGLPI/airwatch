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
r
 ---------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class PluginAirwatchXml {

   var $data;
   var $sxml;
   var $agentbuildnumber;
   var $deviceid;

   function PluginAirwatchXml($data) {
      $this->data = $data;

      $this->deviceid         = $data['DEVICEID'];
      $this->agentbuildnumber = $data['VERSIONCLIENT'];

      $SXML="<?xml version='1.0' encoding='UTF-8'?> \n<REQUEST>\n<CONTENT>";
      $SXML.="<VERSIONCLIENT>{$this->agentbuildnumber}</VERSIONCLIENT>\n";
      $SXML.="</CONTENT>\n";
      $SXML.="<DEVICEID>{$this->deviceid}</DEVICEID>\n<QUERY>INVENTORY</QUERY>\n";
      $SXML.="<PROLOG></PROLOG>\n</REQUEST>";

      $this->sxml = new SimpleXMLElement($SXML);
      $this->setAccessLog();
      $this->setAccountInfos();
      $this->setBios();
      $this->setHardware();
      $this->setOS();
      //$this->setSoftwares();
      $this->setNetwork();
      $this->setAirwatchInfos();
   }

   function setAccessLog() {
      $CONTENT = $this->sxml->CONTENT[0];
      $CONTENT->addChild('ACCESSLOG');

      $ACCESSLOG = $this->sxml->CONTENT[0]->ACCESSLOG;
      $ACCESSLOG->addChild('LOGDATE',date('Y-m-d h:i:s'));

      if(!empty($this->data['userid'])) {
         $this->username = $this->data['userid'];
      }

      $ACCESSLOG->addChild('USERID',$this->username);
   }

   function setNetwork() {
      $CONTENT = $this->sxml->CONTENT[0];
      $i = 0;

      if (isset($this->data['wifi_ipaddress']) || isset($this->data['wifi_macaddress'])) {
         $CONTENT->addChild('NETWORKS');
         $NETWORK = $this->sxml->CONTENT[$i]->NETWORKS;
         if (isset($this->data['wifi_ipaddress'])) {
            $NETWORK->addChild('IPADDRESS',$this->data['wifi_ipaddress']);
         }
         if (isset($this->data['wifi_macaddress'])) {
            $NETWORK->addChild('MACADDR',$this->data['wifi_macaddress']);
         }
         $NETWORK->addChild('TYPE','wifi');
         $i++;
      }
      if (isset($this->data['cellular_ipaddress'])) {
         $CONTENT->addChild('NETWORKS');

         $NETWORK = $this->sxml->CONTENT[$i]->NETWORKS;
         $NETWORK->addChild('IPADDRESS',$this->data['cellular_ipaddress']);
         $NETWORK->addChild('TYPE','ethernet');
      }
   }

   function setAccountInfos() {

      if (isset($this->data['tag'])) {
         $CONTENT = $this->sxml->CONTENT[0];
         $CONTENT->addChild('ACCOUNTINFO');

         $ACCOUNTINFO = $this->sxml->CONTENT[0]->ACCOUNTINFO;
         $ACCOUNTINFO->addChild('KEYNAME','TAG');
         $ACCOUNTINFO->addChild('KEYVALUE',$this->data['tag']);
      }
   }

   function setHardware() {
      $CONTENT = $this->sxml->CONTENT[0];
      $CONTENT->addChild('HARDWARE');

      $HARDWARE = $this->sxml->CONTENT[0]->HARDWARE;
      $HARDWARE->addChild('NAME',$this->data['name']);
      $HARDWARE->addChild('LASTLOGGEDUSER',$this->username);
      $HARDWARE->addChild('UUID',$this->data['uuid']);
      $HARDWARE->addChild('CHASSIS_TYPE',$this->data['type']);
   }

   function setOS() {
      $HARDWARE = $this->sxml->CONTENT[0]->HARDWARE;
      $HARDWARE->addChild('OSNAME'    ,$this->data['osname']);
      $HARDWARE->addChild('OSVERSION' ,$this->data['osversion']);
   }

   function setBios() {
      $CONTENT = $this->sxml->CONTENT[0];
      $CONTENT->addChild('BIOS');

      $BIOS = $this->sxml->CONTENT[0]->BIOS;
      $BIOS->addChild('SMODEL'          ,$this->data['model']);
      $BIOS->addChild('SMANUFACTURER'   ,$this->data['manufacturer']);
      $BIOS->addChild('SSN'             ,$this->data['serial']);
   }

   function setSoftwares() {
      $i = 0;
      $CONTENT = $this->sxml->CONTENT[0];
      foreach ($this->data['applications'] as $application) {
         $application = Toolbox::addslashes_deep($application);
         if (!isset($application['name']) || ! isset($application['version'])) {
            continue;
         }
         $CONTENT->addChild('SOFTWARES');
         $SOFTWARES = $this->sxml->CONTENT[0]->SOFTWARES[$i];
         $SOFTWARES->addChild('NAME', $application['name']);
         $SOFTWARES->addChild('VERSION', $application['version']);
         $SOFTWARES->addChild('PUBLISHER', '');
         $SOFTWARES->addChild('DATEINSTALL', '');
         $i++;
      }
   }

   function setAirwatchInfos() {

      if (isset($this->data['tag'])) {
         $CONTENT = $this->sxml->CONTENT[0];
         $CONTENT->addChild('AIRWATCH');

         $fields = array('PHONENUMBER', 'LASTSEEN', 'ENROLLMENTSTATUS', "LASTENROLLEDON",
                         'COMPLIANCESTATUS', 'COMPROMISEDSTATUS', 'IMEI', 'airwatchid',
                         'CURRENTSIM', 'LASTENROLLMENTCHECKEDON', 'LASTCOMPLIANCECHECKEDON',
                         'LASTCOMPROMISEDCHECKEDON', 'DATAENCRYPTION', 'ROAMINGSTATUS',
                         'DATAROAMINGENABLED', 'VOICEROAMINGENABLED');

         $ACCOUNTINFO = $this->sxml->CONTENT[0]->AIRWATCH;

         foreach ($fields as $field) {
            $ACCOUNTINFO->addChild($field, $this->data[$field]);
         }
      }
   }

}
