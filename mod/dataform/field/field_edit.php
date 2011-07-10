<?php  // $Id$

require_once('../../../config.php');
require_once($CFG->dirroot. '/mod/dataform/mod_class.php');

$d          = required_param('d', PARAM_INT);    // dataform ID

$type       = optional_param('type','' ,PARAM_ALPHA);   // type of a field to edit
$fid        = optional_param('fid',0 ,PARAM_INT);       // field id to edit

// Set a dataform object
$df = new dataform($d);

require_capability('mod/dataform:managetemplates', $df->context);

if ($fid) {
    $field = $df->get_field_from_id($fid);
} else if ($type) {
    $field = $df->get_field($type);
}

$mform = $field->get_form();
//default 'action' for form is strip_querystring(qualified_me())

if ($mform->is_cancelled()){
    if ($d) {
        redirect($CFG->wwwroot.'/mod/dataform/fields.php?d='. $d);
    }

// no submit buttons: reset to default, switch editor    
} else if ($mform->no_submit_button_pressed()) {

// process validated    
} else if ($fromform = $mform->get_data()) { 

    $field->set_field($fromform);
    // add new field
    if (!$fid) {
        $field->insert_field();
        add_to_log($df->course->id, 'dataform', 'fields add',
                   'field_edit.php?d='. $df->id(), '', $df->cm->id);

    // update field
    } else {
        $field->update_field();
        add_to_log($df->course->id, 'dataform', 'fields update',
                   'fields.php?d='. $df->id(). '&amp;fid=', $fid, $df->cm->id);
    }
    
    // go back to fields list
    if ($field->type() != '_entry') {
        redirect($CFG->wwwroot.'/mod/dataform/fields.php?d='. $d);
    } else { // but for entry settings remain
        $displaynoticegood = get_string('entrysettingsupdated','dataform');
    }
}

// Print the browsing interface
$navigation = build_navigation('', $df->cm);
print_header_simple($df->name(), '', $navigation,
                    '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
                    navmenu($df->course, $df->cm), '', '');

print_heading(format_string($df->name()));

// Print the tabs
if ($field->type() == '_entry') {
    $currenttab = 'entry';
} else {
    $currenttab = 'fields';
}
include('../tabs.php');

if (!empty($displaynoticegood)) {
    notify($displaynoticegood, 'notifysuccess');
}

$toform = $field->field;
   
// then set and display
$mform->set_data($toform);

$mform->display();
print_footer($course);

?>