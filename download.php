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
 * eLecta Playback module main user interface
 *
 * @package    mod
 * @subpackage electaplayback
 * @copyright  2013 Chris Egle (http://www.bowenehs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/electaplayback/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // electaplayback instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $electaplayback = $DB->get_record('electaplayback', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('electaplayback', $electaplayback->id, $electaplayback->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('electaplayback', $id, 0, false, MUST_EXIST);
    $electaplayback = $DB->get_record('electaplayback', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/electaplayback:view', $context);

add_to_log($course->id, 'electaplayback', 'view', 'download.php?id='.$cm->id, $electaplayback->id, $cm->id);

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/electaplayback/download.php', array('id' => $cm->id));

// Make sure eLecta Playback exists before generating output - some older sites may contain empty urls
// Do not use PARAM_URL here, it is too strict and does not support general URIs!
$exturl = trim($electaplayback->externalurl);
if (empty($exturl) or $exturl === 'http://') {
    electaplayback_print_header($electaplayback, $cm, $course);
    electaplayback_print_heading($electaplayback, $cm, $course);
    electaplayback_print_intro($electaplayback, $cm, $course);
    notice(get_string('invalidstoredelectaplayback', 'electaplayback'), new moodle_electaplayback('/course/view.php', array('id'=>$cm->course)));
    die;
}
unset($exturl);

// coming from course page or electaplayback index page,
// the redirection is needed for completion tracking and logging
$fullelectaplayback = electaplayback_get_download_url($electaplayback);

redirect(str_replace('&amp;', '&', $fullelectaplayback));
