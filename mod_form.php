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
 * eLectaplayback configuration form
 *
 * @package    mod
 * @subpackage electaplayback
 * @copyright  2013 Chris Egle (@link http://bowenehs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/electaplayback/locallib.php');

class mod_electaplayback_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $config = get_config('electaplayback');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $this->add_intro_editor($config->requiremodintro);

        //-------------------------------------------------------
        $mform->addElement('header', 'content', get_string('contentheader', 'electaplayback'));
        $mform->addElement('url', 'externalurl', get_string('externalurl', 'electaplayback'), array('size'=>'60'), array('usefilepicker'=>false));
        $mform->addRule('externalurl', null, 'required', null, 'client');
        //-------------------------------------------------------
        $mform->addElement('header', 'optionssection', get_string('optionsheader', 'electaplayback'));

				$mform->addElement('checkbox', 'printintro', get_string('printintro', 'electaplayback'));
				$mform->setDefault('printintro', $config->printintro);
				$mform->setAdvanced('printintro', $config->printintro_adv);

				$mform->addElement('checkbox', 'printplay', get_string('printplay', 'electaplayback'));
				$mform->setDefault('printplay', $config->printplay);
				$mform->setAdvanced('printplay', $config->printplay_adv);

				$mform->addElement('checkbox', 'printdownload', get_string('printdownload', 'electaplayback'));
				$mform->setDefault('printdownload', $config->printdownload);
				$mform->setAdvanced('printdownload', $config->printdownload_adv);

        //-------------------------------------------------------
        $mform->addElement('header', 'parameterssection', get_string('parametersheader', 'electaplayback'));
        $mform->addElement('static', 'parametersinfo', '', get_string('parametersheader_help', 'electaplayback'));
        $mform->setAdvanced('parametersinfo');

        if (empty($this->current->parameters)) {
            $parcount = 5;
        } else {
            $parcount = 5 + count(unserialize($this->current->parameters));
            $parcount = ($parcount > 100) ? 100 : $parcount;
        }
        $options = electaplayback_get_variable_options($config);

        for ($i=0; $i < $parcount; $i++) {
            $parameter = "parameter_$i";
            $variable  = "variable_$i";
            $pargroup = "pargoup_$i";
            $group = array(
                $mform->createElement('text', $parameter, '', array('size'=>'12')),
                $mform->createElement('selectgroups', $variable, '', $options),
            );
            $mform->addGroup($group, $pargroup, get_string('parameterinfo', 'electaplayback'), ' ', false);
            $mform->setAdvanced($pargroup);
        }

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        if (!empty($default_values['displayoptions'])) {
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printplay'])) {
                $default_values['printplay'] = $displayoptions['printplay'];
            }
            if (isset($displayoptions['printdownload'])) {
                $default_values['printdownload'] = $displayoptions['printdownload'];
            }
        }
        if (!empty($default_values['parameters'])) {
            $parameters = unserialize($default_values['parameters']);
            $i = 0;
            foreach ($parameters as $parameter=>$variable) {
                $default_values['parameter_'.$i] = $parameter;
                $default_values['variable_'.$i]  = $variable;
                $i++;
            }
        }
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validating Entered url, we are looking for obvious problems only,
        // teachers are responsible for testing if it actually works.

        // This is not a security validation!! Teachers are allowed to enter "javascript:alert(666)" for example.

        // NOTE: do not try to explain the difference between URL and URI, people would be only confused...

        if (empty($data['externalurl'])) {
            $errors['externalurl'] = get_string('required');

        } else {
            $url = trim($data['externalurl']);
            if (empty($url)) {
                $errors['externalurl'] = get_string('required');
            } elseif (preg_match('/^(https?:)(.{6,})(\.(el|EL)[0-9]).*$/', $url)) {
								// normal external URL with an el* extension in it - this is a 'good' URL

						// uncomment the line below and modify the regular expression to match your file location if you have a place where you store your eLecta files locally on the server
						// } else if (preg_match('|^/|', $url) and (preg_match('/\.el', $url))) { 
                // links relative to server root are ok - no validation necessary

            } else  {
                // general URI that doesn't contain a electa extension (el*) - probably not an electa recording URI
                $errors['externalurl'] = get_string('invalidurl', 'electaplayback');
						
            }
        }
        return $errors;
    }

}
