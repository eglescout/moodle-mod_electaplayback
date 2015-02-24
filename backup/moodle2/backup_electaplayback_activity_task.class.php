
<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines backup_electaplayback_activity_task class
 *
 * @package     mod_electaplayback
 * @category    backup
 * @copyright   2010 onwards Andrew Davis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/electaplayback/backup/moodle2/backup_electaplayback_stepslib.php');

/**
 * Provides all the settings and steps to perform one complete backup of the activity
 */
class backup_electaplayback_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the electaplayback.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_electaplayback_activity_structure_step('electaplayback_structure', 'electaplayback.xml'));
    }

    /**
     * Encodes electaplaybacks to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains electaplaybacks to the activity instance scripts
     * @return string the content with the electaplaybacks encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/electaplayback','#');

        //Access a list of all links in a course
        $pattern = '#('.$base.'/index\.php\?id=)([0-9]+)#';
        $replacement = '$@ELECTAPLAYBACKINDEX*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        //Access the link supplying a course module id
        $pattern = '#('.$base.'/view\.php\?id=)([0-9]+)#';
        $replacement = '$@ELECTAPLAYBACKVIEWBYID*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        //Access the link supplying an instance id
        $pattern = '#('.$base.'/view\.php\?u=)([0-9]+)#';
        $replacement = '$@ELECTAPLAYBACKVIEWBYU*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        return $content;
    }
}
