<?php
/**
 * Notice :
 * This page allows access to guests(no login users).
 * Guest users can post only.
 */

namespace mod_sharedpanel;

require_once('../../../config.php');

global $DB;

$cmid = required_param('id', PARAM_INT);
$instanceid = required_param('n', PARAM_INT);
$content = optional_param('cameracomment', null, PARAM_TEXT);
$name = optional_param('name', 'guest', PARAM_TEXT);

if (!is_null($content)) {
    $instance = $DB->get_record('sharedpanel', ['id' => $instanceid]);

    $cardObj = new card($instance);
    $cardid = $cardObj->add($content, $name, 'camera');

    if (array_key_exists('capture', $_FILES)) {
        $cardObj->add_attachment_by_pathname($content, $cardid, $_FILES['tmp_name'], $_FILES['name']);
    }

    echo html_writer::div(get_string('msg_post_success', 'mod_sharedpanel'));
}

echo html_writer::start_tag('html');

echo html_writer::start_tag('head');
echo html_writer::empty_tag('meta', ['http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8']);
echo html_writer::empty_tag('meta', ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.2']);
echo html_writer::tag('title', get_string('post_message', 'mod_sharedpanel'));
//echo html_writer::empty_tag('link', ['href' => 'style-camera.css', 'rel' => 'stylesheet', 'type' => 'text/css']);
echo html_writer::end_tag('head');

echo html_writer::start_tag('body');
echo html_writer::start_tag('form', ['action' => 'com.php', 'method' => 'post', 'enctype' => 'multipart/form-data']);

echo html_writer::start_div();
echo html_writer::tag('label', get_string('message', 'mod_sharedpanel'), ['for' => 'cameracomment']);
echo html_writer::start_div();
echo html_writer::tag('textarea', '', ['name' => 'cameracomment', 'style' => 'width:20em; height:5em;']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div();
echo html_writer::tag('label', get_string('name', 'mod_sharedpanel'), ['for' => 'cameracomment']);
echo html_writer::start_div();
echo html_writer::empty_tag('input', ['name' => 'name', 'type' => 'text']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::empty_tag('hr');

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cmid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'n', 'value' => $instanceid]);

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('message', 'mod_sharedpanel')]);
echo html_writer::end_tag('form');

echo html_writer::end_tag('body');
echo html_writer::end_tag('html');