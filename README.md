phpbb-epgp
================

# epgp extension for phpbb 3.1
EPGP is a relational reward system for World of Warcraft, see http://www.epgpweb.com/

## Installation
Create a `clausi/epgp` folder in your phpBB-Forum extension folder, e.g. `phpBB/ext/clausi/epgp`
Upload all files from the zip directly into your newly created folder. Note: zip file can contain a folder named "phpbb-epgp-master", do not use this, put all files within this folder directly into "clausi/epgp".
Go to your phpBB-Forum "administration panel - customize - manage extension". If uploaded correctly EPGP will show up in not installed list, hit install and enable the extension.


## phpBB permissions
phpbb-epgp will create permissions to administrate and view the epgp system in your forum. Assign these permissions to your groups or specific users.
* User permissions can be found on "user-permissions - misc - can use raidplaner"
* Admin permissions can be found on "administrative permissions - misc - can manage epgp"
Make sure you grant view access for your members and admin access for your epgp-masters.

Templates can also access phpBB template variable {U_EPGP} which will be true if current user has epgp-user privileges.


## epgp modul
### settings
Enable epgp module and select current used guild, this will only be available if already a snapshot of your epgp standings has been uploaded.

### upload
Paste your EPGP-Addon export string here, you can also add an note.

### snapshots
Review and delete your snapshots.
TODO: This page will also provide and import string for your epgp addon.


## Templates
Currently a prosilver based style is included, use this as a starting point for your custom template. 
[Highstocks](http://www.highcharts.com/) is used to view member standings over time.
