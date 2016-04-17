# airwatch

Airwatch connector for GLPi made by Teclib'.

This plugin imports inventory data from Airwatch to GLPi.
For more information, please see http://teclib-edition.com

## Contributing to the plugin

To declare issues or feature requests, please go to [Github](https://github.com/pluginsGLPI/airwatch).
To help translating the plugin, please join  [Transifex](http://transifex.com).

## Prerequisites

php curl extension must be installed.
GLPi 0.90 or higher is required. FusionInventory plugin must be installed and enabled.
FI4GLPI version 0.90+1.3 or lated is required, otherwise you must patch the source code with the following commit :

diff --git a/inc/formatconvert.class.php b/inc/formatconvert.class.php
index b8b39ca..99301e9 100644
--- a/inc/formatconvert.class.php
+++ b/inc/formatconvert.class.php
@@ -311,6 +311,13 @@ class PluginFusioninventoryFormatconvert {
          $a_inventory['fusioninventorycomputer']['operatingsystem_installationdate'] = "NULL";
       }

+      $plugin_values = Plugin::doHook("fusioninventory_addinventoryinfos",
+                                       array('inventory' => $a_inventory,
+                                             'source'    => $array));
+      if (is_array($plugin_values)) {
+         $a_inventory = array_merge($a_inventory, $plugin_values);         
+      }
+
       // * BIOS
       if (isset($array['BIOS'])) {
          if (isset($array['BIOS']['ASSETTAG'])) {
diff --git a/inc/inventorycomputerlib.class.php b/inc/inventorycomputerlib.class.php
index 7297b0b..2365ade 100644
--- a/inc/inventorycomputerlib.class.php
+++ b/inc/inventorycomputerlib.class.php
@@ -1788,6 +1788,8 @@ FALSE);
 //
 //         }

+      Plugin::doHook("fusioninventory_inventory", array('inventory_data' => $a_computerinventory,
+                                                        'computers_id'   => $computers_id ));
       $this->addLog();
    }

## How does it work

The plugin requests Airwatch REST API for:

* general informations (name, OS, etc)
* network informations
* software
* airwatch specific information (enrollement, compliance and comprimse checks, etc)

A XML inventory file is done using the data above, and send to FusionInventory, using curl.
Computers are then created, representing Airwatch devices.

For each device managed by Airwatch, an "Airwatch" tab is display in GLPi. This tab shows Airwatch specific informtions, and allows user to force an inventory

## Rights

There's no specific right for the plugin.
You need, at least, to have the right to see a computer to access Airwatch informations.
To force an Airwatch inventory, you need the right to update a computer.

## Configuration

Configuration options:

* Service URL: URL to send inventories to FusionInventory (by default http://glpi/plugins/fusioninventory/)
* Airwatch web service URL: the REST API URL
* Airwatch console URL: Airwatch web console URL (used to generate a direct Airwatch link for each Airwatch device)
*  Username: Aiwatch user used to access the API
* Password: the password for the user
* API Key: a string defined in the Airwatch administration panel
* Skip SSL checks: access REST API without checking for SSL certificate
