<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * openexam.php - Used to login to the exam to let the teacher signin.
 *
 * @package    plagiarism_tomagrade
 * @subpackage plagiarism
 * @copyright  2021 Tomax ltd <roy@tomax.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->dirroot . '/plagiarism/tomagrade/lib.php');
require_once($CFG->dirroot . '/plagiarism/tomagrade/plagiarism_form.php');
require_login();

global $DB, $CFG, $USER;

$cmid = required_param('cmid', PARAM_INT);
$studentid = optional_param('studentid', null, PARAM_INT);
$groupid = optional_param('groupid', null, PARAM_INT);


if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_SYSTEM);
} else {
    $context = context_system::instance();
}

if (is_null($studentid) && is_null($groupid) ) {
    echo ("<script>alert('".get_string('tomagrade_contactAdmin', 'plagiarism_tomagrade')."');</script>");
    echo ("<script>window.close();</script>");
}
$config = get_config('plagiarism_tomagrade');

$isexam = false;
$matalainfo = tomagrade_get_instance_config($cmid);
if (isset($matalainfo->idmatchontg) &&
 $matalainfo->idmatchontg != '0' &&
  $matalainfo->idmatchontg != '' &&
   is_null($matalainfo->idmatchontg) == false) {
    $isexam = true;
}

$connection = new tomagrade_connection;

if ($isexam) {
    $studentidintg = plagiarism_plugin_tomagrade::get_taodat_zaot($studentid);
} else {
    if (is_null($groupid)) {
        $studentidintg = plagiarism_plugin_tomagrade::get_user_identifier($studentid);
    } else {
        $studentidintg = tomagrade_connection::format_group_name($groupid);
    }
}

$json = $connection->teacher_login($USER->id);

if ($json['LoginStatus'] == "Failed" || $json["UserID"] == $config->tomagrade_username) {
    echo "Login to Tomagrade failed, Tomagrade user ID: " . $json["UserID"] . ", Moodle user ID: " .$USER->id;
    return;
}

$url = "https://$config->tomagrade_server.tomagrade.com/TomaGrade/Server/php/SAMLLogin.php/" .
 $json["Token"] . "/" . $json["UserID"] . "?coursename=" . $matalainfo->examid;

header("Location: $url");
exit;
