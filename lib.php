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
 * Mandatory public API of electaplayback module
 *
 * @package    mod
 * @subpackage electaplayback
 * @copyright  2013 Chris Egle  {@link http://bowenehs.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in electaplayback module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function electaplayback_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return false;
		//		case FEATURE_NO_VIEW_LINK:            return true; //use only to hide the title.

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function electaplayback_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function electaplayback_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function electaplayback_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions
 * @return array
 */
function electaplayback_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add electaplayback instance.
 * @param object $data
 * @param object $mform
 * @return int new electaplayback instance id
 */
function electaplayback_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/electaplayback/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

		// add displayoptions
		$displayoptions['printintro']   = (int)!empty($data->printintro);
		$displayoptions['printplay']   = (int)!empty($data->printplay);
		$displayoptions['printdownload']   = (int)!empty($data->printdownload);
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = electaplayback_fix_submitted_url( $data->externalurl);
		
    $data->timemodified = time();
    $data->id = $DB->insert_record('electaplayback', $data);

    return $data->id;
}

/**
 * Update electaplayback instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function electaplayback_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/electaplayback/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);
		
		// add displayoptions
		$displayoptions['printintro']   = (int)!empty($data->printintro);
		$displayoptions['printplay']   = (int)!empty($data->printplay);
		$displayoptions['printdownload']   = (int)!empty($data->printdownload);
				
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = electaplayback_fix_submitted_url($data->externalurl);
				
    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('electaplayback', $data);

    return true;
}

/**
 * Delete electaplayback instance.
 * @param int $id
 * @return bool true
 */
function electaplayback_delete_instance($id) {
    global $DB;

    if (!$electaplayback = $DB->get_record('electaplayback', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('electaplayback', array('id'=>$electaplayback->id));

    return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $electaplayback
 * @return object|null
 */
function electaplayback_user_outline($course, $user, $mod, $electaplayback) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'electaplayback',
                                              'action'=>'view', 'info'=>$electaplayback->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new stdClass();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return NULL;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $electaplayback
 */
function electaplayback_user_complete($course, $user, $mod, $electaplayback) {
    global $CFG, $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'electaplayback',
                                              'action'=>'view', 'info'=>$electaplayback->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string('neverseen', 'electaplayback');
    }
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return object info
 */
function electaplayback_get_coursemodule_info($coursemodule) {
    global $CFG, $DB, $OUTPUT;
    require_once("$CFG->dirroot/mod/electaplayback/locallib.php");
		
		//flag for identifying Windows machines;  may have to sniff this via javascript and hide links because this info is cached
		/*
		$osagent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($osagent, 'Windows NT') === true and strpos($osagent, 'ARM') === false {
				$osdownload=1;
		}
		*/
		
    if (!$electaplayback = $DB->get_record('electaplayback', array('id'=>$coursemodule->instance),
            'id, name, displayoptions, externalurl, parameters, intro, introformat')) {
        return NULL;
    }
		
    $info = new cached_cm_info();
    $info->name = $electaplayback->name;

    $info->icon = electaplayback_guess_icon($electaplayback->externalurl, 24);
		$info->extraclasses = 'elplay';
		// Get display options
		$options = empty($electaplayback->displayoptions) ? array() : unserialize($electaplayback->displayoptions);
    // Add links and description
		$printlinks='';
		$playbackdetail = '<div class="elplinks">';
		if (!empty($options['printplay'])) {
				// play link
				$fullurl ="$CFG->wwwroot/mod/electaplayback/view.php?id=$coursemodule->id&amp;redirect=1";
				$playbackdetail .= '<a class="elplaybtn" href="'.get_string('wwwroot','mod_electaplayback').'/mod/electaplayback/view.php?id='.$coursemodule->id . '" target="electastart" title="'.get_string('playurllabel','mod_electaplayback',$electaplayback->name).'">'.get_string('playurllabel','mod_electaplayback').'</a>';
				$printlinks=1;
		}
		if (!empty($options['printdownload'])) {
				//download link
				$playbackdetail .= '<a class="eldownloadbtn" href="'.get_string('wwwroot','mod_electaplayback').'/mod/electaplayback/download.php?id='.$coursemodule->id . '" title="'.get_string('downloadurllabel','mod_electaplayback',$electaplayback->name).': '.get_string('downloadexplainshort','mod_electaplayback').'">'.get_string('downloadurllabel','mod_electaplayback').'</a>';
				
				if (empty($printlinks)) { 
					$fullurl ="$CFG->wwwroot/mod/electaplayback/download.php?id=$coursemodule->id&amp;redirect=1"; 
					$printlinks=1;
				}
		}
		if (!empty($printlinks)) {
				// set link only if the play or download options are checked
				$info->onclick = "window.open('$fullurl','electastart'); return false;";//include window 'electastart' for the play link
		} else {
				$playbackdetail .= '<span>' . get_string('nolinks','mod_electaplayback') . '</span>';
				$fullurl ="$CFG->wwwroot/course/view.php?id=$coursemodule->course"; 
				$info->onclick = "window.open('$fullurl'); return false;";
		}
		$playbackdetail .= '</div>';
		if (!empty($options['printintro'])) {
				$playbackdetail .= '<strong class="elpcontent">' . get_string('contentlabel','mod_electaplayback') .'</strong>';
				// Convert intro to html. Do not filter cached version, filters run at display time.
				$playbackdetail .= format_module_intro('electaplayback', $electaplayback, $coursemodule->id, false);
    }
				
		$info->content = $playbackdetail;
		

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function electaplayback_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-electaplayback-*'=>get_string('page-mod-electaplayback-x', 'electaplayback'));
    return $module_pagetype;
}

/**
 * Export Electa Playback resource contents
 *
 * @return array of file content
 */
function electaplayback_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/electaplayback/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $electaplayback = $DB->get_record('electaplayback', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fullurl = str_replace('&amp;', '&', electaplayback_get_full_url($electaplayback, $coursemodule, $course));
    $isurl = clean_param($fullurl, PARAM_URL);
    if (empty($isurl)) {
        return null;
    }

    $electaplayback = array();
    $electaplayback['type'] = 'url';
    $electaplayback['filename']     = $electaplayback->name;
    $electaplayback['filepath']     = null;
    $electaplayback['filesize']     = 0;
    $electaplayback['fileurl']      = $fullurl;
    $electaplayback['timecreated']  = null;
    $electaplayback['timemodified'] = $electaplayback->timemodified;
    $electaplayback['sortorder']    = null;
    $electaplayback['userid']       = null;
    $electaplayback['author']       = null;
    $electaplayback['license']      = null;
    $contents[] = $electaplayback;

    return $contents;
}
