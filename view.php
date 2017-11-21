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
 * @package    mod_sharedpanel
 * @copyright  2016 NAGAOKA Chikako, KITA Toshihiro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $DB, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);
$sortby = optional_param('sortby', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('sharedpanel', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $sharedpanel = $DB->get_record('sharedpanel', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $sharedpanel = $DB->get_record('sharedpanel', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $sharedpanel->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('sharedpanel', $sharedpanel->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

$context = context_module::instance($cm->id);
require_login();

// Groupカード（カテゴリ分け）を表示するかどうか
$sharedpanel_dispgcard = true;
// ２つ目のいいねを使うか
$sharedpanel_likes2 = true;
// sender を表示するかどうか
$dispname = false;

// パネル毎にCSSを変える ... 仮実装
//$styfile= $CFG->dataroot.'/sharedpanel/style.css.'.$sharedpanel->id;
$styfile = __DIR__ . '/css/style.css.' . $sharedpanel->id;
if (file_exists($styfile)) {
    $PAGE->requires->css($styfile);
} else {
    $PAGE->requires->css(new moodle_url("style.css"));
}

// Print the page header.
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js(new moodle_url('js/jsPlumb-2.1.5-min.js'));
$PAGE->requires->js(new moodle_url('js/card_admin.js'));

$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_url('/mod/sharedpanel/view.php', array('id' => $cm->id));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($sharedpanel->name));
$PAGE->set_pagelayout('incourse');

//Call Objects
$cardObj = new \mod_sharedpanel\card($sharedpanel);

// Output starts here.
echo $OUTPUT->header();

echo html_writer::start_div();

echo html_writer::empty_tag('hr');
echo get_string('sortedas', 'sharedpanel');
echo \html_writer::start_div('btn-toolbar');

echo html_writer::start_div('btn-group');
echo html_writer::link(new moodle_url('view.php', ['id' => $id]), get_string('sort', 'sharedpanel'), ['class' => 'btn']);
echo html_writer::end_div();

echo html_writer::start_div('btn-group');
echo html_writer::link(new moodle_url('view.php', ['id' => $id, 'sortby' => 1]), get_string('sortbylike1', 'sharedpanel'), ['class' => 'btn']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::empty_tag('hr');

echo \html_writer::start_div('btn-toolbar');
echo html_writer::start_div('btn-group');
echo html_writer::link(new moodle_url('camera/com.php', ['id' => $id, 'n' => $sharedpanel->id]), get_string('postmessage', 'sharedpanel'), ['class' => 'btn']);
echo html_writer::end_div();
echo html_writer::start_div('btn-group');
echo html_writer::empty_tag('input',
    ['type' => 'button', 'value' => get_string('print', 'sharedpanel'), 'onclick' => 'window.print()', 'style' => 'margin:1ex;', 'class' => 'btn']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::empty_tag('hr');

if (has_capability('moodle/course:manageactivities', $context)) {
    echo \html_writer::start_div('btn-toolbar');

    echo \html_writer::start_div('btn-group');
    echo html_writer::link(new moodle_url('importcard.php', ['id' => $id]), get_string('import', 'sharedpanel'), ['class' => 'btn']);
    echo html_writer::end_div();

    echo \html_writer::start_div('btn-group');
    echo html_writer::link(new moodle_url('importcard.php', ['id' => $id]), get_string('import', 'sharedpanel'), ['class' => 'btn']);
    echo html_writer::end_div();

    echo \html_writer::start_div('btn-group');
    echo html_writer::link(new moodle_url('post.php', ['id' => $id, 'sesskey' => sesskey()]), get_string('post', 'sharedpanel'), ['class' => 'btn']);
    echo html_writer::end_div();

    echo \html_writer::start_div('btn-group');
    echo html_writer::link(new moodle_url('gcard.php', ['id' => $id]), get_string('groupcard', 'sharedpanel'), ['class' => 'btn']);
    echo html_writer::end_div();

    echo html_writer::end_div();
}

echo html_writer::empty_tag('hr');

// CARDのデータをDBから取得
$ratingmap = [];
if ($sortby) {
    $cards = $cardObj->get_cards('important');
} else {
    $cards = $cardObj->get_cards('like');
}

// Group (Category) Card
$gcards = $cardObj->get_gcards(0, 'rating DESC');

echo html_writer::start_div('', ['id' => 'diagramContainer']);

// Groupカード ----------------------------------------------
if ($sharedpanel_dispgcard) {
    $leftpos = 300;
    $toppos = 300;
    $gcardnum = 0;
    foreach ($gcards as $gcard) {  // 各Groupカード
        if ($gcard->positionx == 0 and $gcard->positiony == 0) {
            $tstyle = "left:${leftpos}px;top:${toppos}px;";
            $leftpos += 300;
            $toppos += 10;
            if ($leftpos > 1200) {
                $leftpos = 300;
                $toppos += 440;
            }
        } else {
            $tstyle = "left:" . $gcard->positionx . "px;top:" . $gcard->positiony . "px;'";
        }
        $gcardnum = $gcard->id;

        // コンテンツ要素
        $gcardcontent = $gcard->content;

        // 上記要素を使って、Groupカードの表示
        $tstyle .= ' width:' . $gcard->sizex . 'px';
        echo html_writer::start_div('all-style0 card', ['id' => 'gcard' . $gcardnum, 'style' => $tstyle]);
        echo html_writer::div($gcardcontent, 'all-style2', ['style' => 'height:' . $gcard->sizey . 'px; width:' . $gcard->sizex . 'px;']);
        echo html_writer::start_div('all-style3', ['style' => 'width:' . $gcard->sizex . 'px;']);
        echo html_writer::span($likeslink);
        // 削除リンク要素 （教師だけに表示）
        if (has_capability('moodle/course:manageactivities', $context)) {
            $dellink = html_writer::link(new moodle_url('delgcard.php', ['id' => $id, 'c' => $gcard->id]), '削除する');
            echo html_writer::span($dellink, ['style' => 'margin-left:5em;']);
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}
// Groupカード ----------------------------------------------

$leftpos = 420;
$toppos = 370;
$cnum = 0;
foreach ($cards as $card) {  // 各カード
    if ($card->positionx == 0 and $card->positiony == 0) {
        $tstyle = "style='left:${leftpos}px;top:${toppos}px;'";
        $leftpos += 300;
        $toppos += 10;
        if ($leftpos > 1200) {
            $leftpos = 420;
            $toppos += 440;
        }
    } else {
        $tstyle = "style='left:" . $card->positionx . "px;top:" . $card->positiony . "px;'";
    }

    echo \mod_sharedpanel\html_writer::card($context, $card, $tstyle);

    $cnum++;
}  // foreach ($cards as $card)
echo html_writer::end_div();

echo '(total: ' . $cnum . 'cards)';

//----------------------------------------------------------------------------
// Finish the page.
echo $OUTPUT->footer();