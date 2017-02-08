# Airwatch GLPi plugin

Airwatch connector for GLPi made by Teclib'.

This plugin imports inventory data from Airwatch to GLPi.
For more information, please see http://teclib-edition.com

## Contributing to the plugin

To declare issues or feature requests, please go to [Github](https://github.com/pluginsGLPI/airwatch).
To help translating the plugin, please join  [Transifex](http://transifex.com).

## Prerequisites

* php curl extension must be installed.
* GLPi 9.1 or higher is required.
* FusionInventory plugin must be installed and enabled.

## How does it work

The plugin requests Airwatch REST API for:

* general informations (name, OS, etc)
* network informations
* software
* airwatch specific information (enrollement, compliance and comprimse checks, etc)

A XML inventory file is done using the data above, and send to FusionInventory, using curl.
Computers are then created, representing Airwatch devices.

For each device managed by Airwatch, an "Airwatch" tab is displayed in GLPi. This tab shows Airwatch specific informtions, and allows user to force an inventory

## Rights

There's no specific right for the plugin.
You need, at least, to have the right to see a computer to access Airwatch informations.
To force an Airwatch inventory, you need the right to update a computer.

## Configuration

Configuration options:

* Service URL: URL to send inventories to FusionInventory (by default `http://glpi/plugins/fusioninventory/`)
* Airwatch web service URL: the REST API URL
* Airwatch console URL: Airwatch web console URL (used to generate a direct Airwatch link for each Airwatch device)
* Username: Aiwatch user used to access the API
* Password: the password for the user
* API Key: a string defined in the Airwatch administration panel
* Skip SSL checks: access REST API without checking for SSL certificate

## Contributing

* Open a ticket for each bug/feature so it can be discussed
* Follow [development guidelines](http://glpi-developer-documentation.readthedocs.io/en/latest/plugins.html)
* Refer to [GitFlow](http://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer
