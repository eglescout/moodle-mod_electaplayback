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
 * Url module admin settings and defaults
 *
 * @package    mod
 * @subpackage electaplayback
 * @copyright  2013 Chris Egle (@link http://bowenehs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
		
    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configcheckbox('electaplayback/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));
    $settings->add(new admin_setting_configpasswordunmask('electaplayback/secretphrase', get_string('password'),
        get_string('configsecretphrase', 'electaplayback'), ''));
    $settings->add(new admin_setting_configcheckbox('electaplayback/rolesinparams',
        get_string('rolesinparams', 'electaplayback'), get_string('configrolesinparams', 'electaplayback'), false));
		$settings->add(new admin_setting_configtext('electaplayback/playurlprefix', get_string('playurlprefix', 'electaplayback'), 
				get_string('playurlprefix_desc', 'electaplayback'), get_string('playurlprefix_default', 'electaplayback'), PARAM_URL));


    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('urlmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('electaplayback/printintro',
        get_string('printintro', 'electaplayback'), get_string('printintroexplain', 'electaplayback'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configcheckbox_with_advanced('electaplayback/printplay',
        get_string('printplay', 'electaplayback'), get_string('printplayexplain', 'electaplayback'),
        array('value'=>1, 'adv'=>false)));
		$settings->add(new admin_setting_configcheckbox_with_advanced('electaplayback/printdownload',
        get_string('printdownload', 'electaplayback'), get_string('printdownloadexplain', 'electaplayback'),
        array('value'=>1, 'adv'=>false)));
}    
