# Analytics for Course Engagement (ACE) #

Analytics for Course Engagement (ACE) is a platform that provides students and staff with an enhanced real-time view of online engagement with learning systems. It builds on top of the Moodle Analytics API https://moodledev.io/docs/apis/subsystems/analytics/ and the new Reportbuilder API.

The University of Canterbury is currently running this on a heavily modified version of Moodle 3.9 that contains a backport of Moodle's reportbuilder API but are currently working on a version for Moodle 4.1.

It also currently requires the following trackers which we have been working on and trying to get into Moodle core.
* https://tracker.moodle.org/browse/MDL-73184 (now in 4.1)
* https://tracker.moodle.org/browse/MDL-75170 (now in 4.1)
* https://tracker.moodle.org/browse/MDL-73294 
* https://tracker.moodle.org/browse/MDL-72974
* https://tracker.moodle.org/browse/MDL-73104
* https://tracker.moodle.org/browse/MDL-72831

We also use the a custom dashboard plugin (based on local_vxgdashboard) and a block that allows us to embed reportbuilder sources within the dashboard which is not currently available as an open-source plugin.

## Branches

| Moodle version    | Branch             |
| ----------------- | ------------------ |
| Moodle 3.9       | `MOODLE_39_STABLE` |
| Moodle 4.0       | `MOODLE_40_STABLE` |
| 4.1 (under development | `MOODLE_401_STABLE` |

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/ace

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2021 University of Canterbury

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
