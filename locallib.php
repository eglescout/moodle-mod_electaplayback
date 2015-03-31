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
 * Private electaplayback module utility functions
 *
 * @package    mod
 * @subpackage electaplayback
 * @copyright  2009 Petr Skoda  {@link http://skodak.org} , 2013 Chris Egle 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/electaplayback/lib.php");

/**
 * This methods does weak electaplayback url validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $electaplayback
 * @return bool true is seems valid, false if definitely not valid URL
 */
function electaplayback_appears_valid_url($electaplayback) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $electaplayback)) {
        // note: this is not exact validation, we look for severely malformed URLs only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $electaplayback);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $electaplayback);
    }
}

/**
 * Fix common URL problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $electaplayback
 * @return string
 */
function electaplayback_fix_submitted_url($electaplayback) {
    // note: empty urls are prevented in form validation
    $electaplayback = trim($electaplayback);

    // remove encoded entities - we want the raw URI here
    $electaplayback = html_entity_decode($electaplayback, ENT_QUOTES, 'UTF-8');
		
		if (preg_match('/\?disposition/i', $electaplayback)) {
				// remove download suffix
				$dtrimlength = strpos($electaplayback,'?disposition');
				$electaplayback = substr($electaplayback,0,$dtrimlength);

		} else if (preg_match('/play_uni\.asp\?url=/i', $electaplayback)) {
				// remove playable link part
				$dtrimlength = strpos($electaplayback,'.asp?url=') + 9; // include the number of characters in the search string so that they'll be removed too.
				$electaplayback = substr($electaplayback,$dtrimlength);
		}	

    return $electaplayback;
}

 /**
 * Return download url
 *
 * This function does not include any XSS protection.
 *
 * @param object $electaplayback
 * @return string url with & encoded as &amp;
 */
 
 function electaplayback_get_download_url($electaplayback) {

    // make sure there are no encoded entities, it is ok to do this twice
    $fullurl = html_entity_decode($electaplayback->externalurl, ENT_QUOTES, 'UTF-8');
		
		// add the download info to the URL
		$file_ext = electaplayback_get_extension($fullurl);
		$download_name = str_replace(' ','_',$electaplayback->name);
		$downloadsuffixname  = get_string('downloadsuffix', 'electaplayback') . ';filename=' . $download_name . '.' . $file_ext;
		$fullurl .= $downloadsuffixname;
		
    if (preg_match('/^(\/|https?:|ftp:)/i', $fullurl) or preg_match('|^/|', $fullurl)) {
        // encode extra chars in URLs - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullurl = preg_replace_callback("/[^$allowed]/", 'electaplayback_filter_callback', $fullurl);
    } else {
        // encode special chars only
        $fullurl = str_replace('"', '%22', $fullurl);
        $fullurl = str_replace('\'', '%27', $fullurl);
        $fullurl = str_replace(' ', '%20', $fullurl);
        $fullurl = str_replace('<', '%3C', $fullurl);
        $fullurl = str_replace('>', '%3E', $fullurl);
    }

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}
 
/**
 * Return extension of electa file
 *
 * @param string $electaplayback
 * @return string
 */
function electaplayback_get_extension($electaplayback) {
		// URL should already be trimmed and validated
		$dtrimlength = strpos($electaplayback,'.el') + 1;
		$electaplayback = substr($electaplayback,$dtrimlength);
		return $electaplayback;
}

/**
 * Return full url with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $electaplayback
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string url with & encoded as &amp;
 */
function electaplayback_get_full_url($electaplayback, $cm, $course, $config=null) {

    $parameters = empty($electaplayback->parameters) ? array() : unserialize($electaplayback->parameters);

    // make sure there are no encoded entities, it is ok to do this twice
    $fullurl = html_entity_decode($electaplayback->externalurl, ENT_QUOTES, 'UTF-8');

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullurl) or preg_match('|^/|', $fullurl)) {
        // encode extra chars in URLs - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullurl = preg_replace_callback("/[^$allowed]/", 'electaplayback_filter_callback', $fullurl);
    } else {
        // encode special chars only
        $fullurl = str_replace('"', '%22', $fullurl);
        $fullurl = str_replace('\'', '%27', $fullurl);
        $fullurl = str_replace(' ', '%20', $fullurl);
        $fullurl = str_replace('<', '%3C', $fullurl);
        $fullurl = str_replace('>', '%3E', $fullurl);
    }

    // add variable url parameters
    if (!empty($parameters)) {
        if (!$config) {
            $config = get_config('electaplayback');
        }
        $paramvalues = electaplayback_get_variable_values($electaplayback, $cm, $course, $config);

        foreach ($parameters as $parse=>$parameter) {
            if (isset($paramvalues[$parameter])) {
                $parameters[$parse] = rawurlencode($parse).'='.rawurlencode($paramvalues[$parameter]);
            } else {
                unset($parameters[$parse]);
            }
        }

        if (!empty($parameters)) {
            if (stripos($fullurl, 'teamspeak://') === 0) {
                $fullurl = $fullurl.'?'.implode('?', $parameters);
            } else {
                $join = (strpos($fullurl, '?') === false) ? '?' : '&';
                $fullurl = $fullurl.$join.implode('&', $parameters);
            }
        }
    }

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Return the full playable URL of an electa file
 *
 * @param object $electaplayback
 * @return string url with & encoded as &amp;
 */

function electaplayback_get_playable_url($electaplayback) {
  
    // make sure there are no encoded entities, it is ok to do this twice
    $fullurl = html_entity_decode($electaplayback->externalurl, ENT_QUOTES, 'UTF-8');

    // add playable url prefix
    $config = get_config('electaplayback');
	//	$prefix = $config->playurlprefix;
    $domain = $config->playurldomain;
    $play_params = '/tools/play_uni.asp?url=';
		$fullurl = $domain . $play_params . $fullurl;
		
	  // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */

 function electaplayback_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print electaplayback header.
 * @param object $electaplayback
 * @param object $cm
 * @param object $course
 * @return void
 */ 
function electaplayback_print_header($electaplayback, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$electaplayback->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($electaplayback);
    echo $OUTPUT->header();
}

/**
 * Print electaplayback heading.
 * @param object $electaplayback
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function electaplayback_print_heading($electaplayback, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($electaplayback->displayoptions) ? array() : unserialize($electaplayback->displayoptions);

  //  if ($ignoresettings or !empty($options['printheading'])) {
        echo $OUTPUT->heading(format_string($electaplayback->name), 2, 'main', 'electaplaybackheading');
  //  }
}

/**
 * Print electaplayback introduction.
 * @param object $electaplayback
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function electaplayback_print_intro($electaplayback, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($electaplayback->displayoptions) ? array() : unserialize($electaplayback->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($electaplayback->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'electaplaybackintro');
            echo format_module_intro('electaplayback', $electaplayback, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}


/**
 * Get the parameters that may be appended to electaplayback URL
 * @param object $config electaplayback module config options
 * @return array array describing opt groups
 */
function electaplayback_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'electaplayback'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'electaplayback')] = array(
        'electaplaybackinstance'     => 'id',
        'electaplaybackcmid'         => 'cmid',
        'electaplaybackname'         => get_string('name'),
        'electaplaybackidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverurl'       => get_string('serverurl', 'electaplayback'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone').' 1',
        'userphone2'      => get_string('phone2').' 2',
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userurl'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to electaplayback URL
 * @param object $electaplayback module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function electaplayback_get_variable_values($electaplayback, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverurl'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'urlinstance'     => $electaplayback->id,
        'urlcmid'         => $cm->id,
        'urlname'         => format_string($electaplayback->name),
        'urlidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $values['usertimezone']    = get_user_timezone_offset();
        $values['userurl']         = $USER->url;
    }

    // weak imitation of Single-Sign-On, for backwards compatibility only
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    //       needs to be createed if somebody needs the old functionality!
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = electaplayback_get_encrypted_parameter($electaplayback, $config);
    }

    return $values;
}

/**
 * BC internal function
 * @param object $electaplayback
 * @param object $config
 * @return string
 */
function electaplayback_get_encrypted_parameter($electaplayback, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($electaplayback, $config);
        }
    }
    return md5(getremoteaddr().$config->secretphrase);
}

/**
 * Optimised mimetype detection from general URL
 * @param $fullurl
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function electaplayback_guess_icon($fullurl, $size = null) {
    global $CFG;
    return null;
}
