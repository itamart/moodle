<?php  // $Id$

// This file to be included so we can assume config.php has already been included.
// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab) or empty($df->data) or empty($df->course)) {
        error('You cannot call this script in that way');
    }

    $inactive = NULL;
    $activetwo = NULL;
    $tabs = array();
    $row = array();

    // Browse tab
    $row[] = new tabobject('browse', $CFG->wwwroot.'/mod/dataform/view.php?d='.$df->id(), get_string('browse','dataform'));

    if (isloggedin()) {
        // Grades tab
        //if ($df->data->rating and has_capability('mod/dataform:writeentry', $df->context)) {
        //    $row[] = new tabobject('grade', $CFG->wwwroot.'/mod/dataform/grade.php?d='.$df->id(), get_string('grade','dataform'));
        //}
        
        // Management tab
        if (has_capability('mod/dataform:managetemplates', $df->context)) {
            $row[] = new tabobject('manage', $CFG->wwwroot.'/mod/dataform/fields.php?d='.$df->id(), get_string('manage','dataform'));
        }
    }

    $tabs[] = $row;

    if ($currenttab != 'browse') {
        $inactive = array();
        $inactive[] = 'manage';

        $row  = array();

        if (isloggedin()) {
            if (has_capability('mod/dataform:managetemplates', $df->context)) {
                $row[] = new tabobject('fields', $CFG->wwwroot.'/mod/dataform/fields.php?d='.$df->id(),
                             get_string('fields','dataform'));
                $row[] = new tabobject('views', $CFG->wwwroot.'/mod/dataform/views.php?d='.$df->id(),
                             get_string('views','dataform'));
                $row[] = new tabobject('filters', $CFG->wwwroot.'/mod/dataform/filters.php?d='.$df->id(),
                             get_string('filters','dataform'));
                $row[] = new tabobject('entry', $CFG->wwwroot.'/mod/dataform/field/field_edit.php?d='.$df->id().'&amp;fid=-1',
                             get_string('entry','dataform'));
                $row[] = new tabobject('js', $CFG->wwwroot.'/mod/dataform/js.php?d='.$df->id().'&amp;edit=1',
                             get_string('jsinclude', 'dataform'));
                $row[] = new tabobject('css', $CFG->wwwroot.'/mod/dataform/css.php?d='.$df->id().'&amp;edit=1',
                             get_string('cssinclude', 'dataform'));
                $row[] = new tabobject('presets', $CFG->wwwroot.'/mod/dataform/presets.php?d='.$df->id(),
                             get_string('presets', 'dataform'));
                $row[] = new tabobject('import', $CFG->wwwroot.'/mod/dataform/import.php?d='.$df->id(),
                             get_string('import', 'dataform'));
                $row[] = new tabobject('export', $CFG->wwwroot.'/mod/dataform/export.php?d='.$df->id(),
                             get_string('export', 'dataform'));
            }
        }

        $tabs[] = $row;
        $activetwo = array('manage');
    }
    // Print out the tabs and continue!
    print_tabs($tabs, $currenttab, $inactive, $activetwo);
?>