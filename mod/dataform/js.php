<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Martin Dougiamas  http://dougiamas.com             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

require_once('../../config.php');

$d = optional_param('d', 0, PARAM_INT);   // dataform id
$edit = optional_param('edit', 0, PARAM_BOOL);   // edit mode

if ($edit) {
    require_once('lib.php');

    // Set a dataform object
    $df = new dataform($d);

    require_login($df->course->id, false, $df->cm);
    $df->context = get_context_instance(CONTEXT_MODULE, $df->cm->id);
    require_capability('mod/dataform:managetemplates', $df->context);

    $navigation = build_navigation('', $df->cm);
    print_header_simple($df->name(), '', $navigation,
                        '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
                        navmenu($df->course, $df->cm), '', '');

    print_heading(format_string($df->name()));

    // Print the tabs.
    $currenttab = 'js';
    include('tabs.php');

    if (($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/js.php')) and confirm_sesskey()) {
        $rec = new object();
        $rec->id = $df->id();
        $rec->js = $forminput->js;

        if (update_record('dataform', $rec)) {
            notify(get_string('jssaved', 'dataform'), 'notifysuccess');
        }

        add_to_log($df->course->id, 'dataform', 'js saved', 'js.php?id='. $df->cm->id. '&amp;d='. $df->id(), $df->id(), $df->cm->id);
    } else {
        echo '<div class="littleintro" style="text-align:center;padding:5px;">'.get_string('headerjs', 'dataform').'</div>';
    }

    echo '<form id="jsform" action="js.php?d='.$df->id().'&amp;edit=1" method="post">';
    echo '<div>';
    echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />';
    // Print button to autogen all forms, if all templates are empty

    print_simple_box_start('center','80%');
    echo '<table align="center" cellpadding="4" cellspacing="0" border="0">';

    // reload
    $df->data = get_record('dataform', 'id', $df->id());
   
    // Print the main template.
    echo '<tr><td style="text-align:center">';
    print_textarea(false, 20, 72, 0, 0, 'js', $df->data->js);
    echo '</td></tr>';

    echo '<tr><td style="text-align:center">';
    echo '<input type="submit" value="'.get_string('save','dataform').'" />';
    echo '</td></tr>';
    echo '</table>';


    print_simple_box_end();
    echo '</div>';
    echo '</form>';

    // Finish the page
    print_footer($df->course);

} else {

    $lifetime  = 600;                                   // Seconds to cache this stylesheet
    $nomoodlecookie = true;                             // Cookies prevent caching, so don't use them

    if ($data = get_record('dataform', 'id', $d)) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $lifetime) . ' GMT');
        header('Cache-control: max_age = '. $lifetime);
        header('Pragma: ');
        header('Content-type: text/javascript');  // Correct MIME type

        echo $data->js;
    }
}
?>