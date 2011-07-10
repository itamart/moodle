<?php // $Id$

//  For a given post, shows a report of all the ratings/comments it has

require_once("../../config.php");
require_once("mod_class.php");

$rid   = required_param('rid', PARAM_INT);
$fid   = required_param('fid', PARAM_INT);
$show = optional_param('show', 0, PARAM_BOOL);
$edit = optional_param('edit', 0, PARAM_BOOL);
$add = optional_param('add', 0, PARAM_BOOL);
$delete   = optional_param('delete', 0, PARAM_INT);
$sort = optional_param('sort', '', PARAM_ALPHA);

if (!$record = get_record('dataform_entries', 'id', $rid)) {
    error("Record ID is incorrect");
}

// Set a dataform object
$df = new dataform($record->dataid);

if (!$field = $df->get_field_from_id($fid)) {
    error("Field ID is incorrect");
}

// submission processing
if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/popup.php') and confirm_sesskey()) {
    // field submitted for update so update its content
    foreach ($forminput as $name => $value){
        if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
            $field->update_content($rid, $value, $name);
        }
    }
}

if ($delete) {
    $field->delete_content($rid, $delete);
}

print_header($field->name());

if ($show) {
    echo $field->display_popup($record, array('show' => 1, 'sort' => $sort));
}

if ($edit) {

    // make form for the field editing
    echo '<form enctype="multipart/form-data" id="popupform" action="popup.php" method="post">',
        '<div>',
        '<input type="hidden" name="rid" value="', $rid, '" />',
        '<input type="hidden" name="fid" value="', $fid, '" />',
        '<input type="hidden" name="sesskey" value="', sesskey(), '" />',
        '<input type="hidden" name="show" value="'.$show.'" />',
        '<input type="hidden" name="edit" value="1" />',
        '</div>',

        $field->display_popup($record, array('edit' => 1)),
        
        '<div style="text-align:center">',
        '<input type="submit" name="savechanges" value="', get_string('savechanges'), '" onclick="opener.location.reload();" />',
        '&nbsp;<input type="button" name="close" value="', get_string('closewindow'), '" onclick="opener.location.reload();window.close();" />',
        '</div>',
        '</form>';
        
    // print after form stuff
    $field->print_after_form();
    
    // enable html editor if needed
    if ($field->type == 'textarea' and $field->is_editor()) {
        $field->use_html_editor($rid);
    }
}

print_footer('none');
?>