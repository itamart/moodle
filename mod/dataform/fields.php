<?php  // $Id$

require_once('../../config.php');
require_once('mod_class.php');


$d          = optional_param('d', 0, PARAM_INT);             // dataform id
$id         = optional_param('id', 0, PARAM_INT);            // course module id
$fid        = optional_param('fid', 0 , PARAM_INT);          // update field id

// fields list actions
$new        = optional_param('new','',PARAM_ALPHA);     // type of the new field

$edit       = optional_param('edit', 0, PARAM_INT);     // id of field to edit
$delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of fields to delete
$duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of fields to duplicate

$confirm    = optional_param('confirm', 0, PARAM_INT);

// field actions
$add        = optional_param('add', 0, PARAM_INT);      // add new field
$update     = optional_param('update', 0, PARAM_INT);   // update field
$cancel     = optional_param('cancel', '');

// Set a dataform object
$df = new dataform($d, $id);

require_capability('mod/dataform:managetemplates', $df->context);

// Print the browsing interface
$navigation = build_navigation('', $df->cm);
print_header_simple($df->name(), '', $navigation,
                    '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
                    navmenu($df->course, $df->cm), '', '');

print_heading(format_string($df->name()));

// DATA PROCESSING
if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/fields.php') and confirm_sesskey()) {
    // default sort
    if (!empty($forminput->updatedefaultsort)) {
        $sortlist = array();

        // set fields' sort order and direction
        if ($fields = $df->get_fields(array(-1))) {
            foreach ($fields as $field) {
                $fieldid = $field->field->id;
                if ($fieldid and $defaultsortorder = optional_param('defaultsort'. $fieldid, 0, PARAM_INT)) {
                    $sortlist[$defaultsortorder] = array($fieldid, optional_param('defaultdir'. $fieldid, 0, PARAM_INT));
                }
            }
        }
        
        // update dataform default sort
        if (!empty($sortlist)) {
            ksort($sortlist);
            $strsort = serialize($sortlist);
        } else {
            $strsort = '';
        }
        $rec->id = $df->id();
        $rec->defaultsort = $strsort;
        if (!update_record('dataform', $rec)) {
            error('There was an error updating the dataform');
        }
        // update current record so that the list reflects the changes
        $df->data->defaultsort = $strsort;
    
    // multi add or delete
    } else if (!empty($forminput->multiduplicate) or !empty($forminput->multidelete)) {
        $fids = array();
        foreach ($forminput as $name => $checked) {
            if (strpos($name, 'fieldselector_') !== false) {
                if ($checked) {
                    $namearr = explode('_', $name);  // Second one is the field id                   
                    $fids[] = $namearr[1];
                }
            }
        }
        
        if ($fids) {
            if (!empty($forminput->multiduplicate)) {
                $duplicate = implode(',', $fids);
            } else if (!empty($forminput->multidelete)) {
                $delete = implode(',', $fids);        
            }
        }
    }
}

if ($duplicate and confirm_sesskey()) {   // Duplicate any requested fields
    $df->process_fields('duplicate', $duplicate, $confirm);

} else if ($delete and confirm_sesskey()) { // Delete any requested fields
    $df->process_fields('delete', $delete, $confirm);
}

// Print the tabs
$currenttab = 'fields';
include('tabs.php');

// Notifications first
if (!$fields = $df->get_fields()) {
    notify(get_string('nofieldindataform','dataform'));  // nothing in dataform
    notify(get_string('pleaseaddsome','dataform', 'presets.php?d='.$df->id())); // link to presets
}

// Display the field form jump list
$directories = get_list_of_plugins('mod/dataform/field/');
$menufield = array();

foreach ($directories as $directory){
    if ($directory[0] != '_') {
        $menufield[$directory] = get_string($directory,'dataform');    //get from language files
    }
}
asort($menufield);    //sort in alphabetical order

echo '<br />';
echo '<div class="fieldadd">';
echo '<label for="fieldform_jump">'.get_string('newfield','dataform').'</label>&nbsp;';
popup_form($CFG->wwwroot.'/mod/dataform/field/field_edit.php?d='.$df->id().'&amp;sesskey='.
        sesskey().'&amp;type=', $menufield, 'fieldform', '', 'choose');
helpbutton('fields', get_string('addafield','dataform'), 'dataform');
echo '</div>';
echo '<br />';

// if there are user fields print admin style list of them
if ($fields) {
    
    echo '<form id="sortdefault" action="'.$CFG->wwwroot.'/mod/dataform/fields.php" method="post">',
        '<input type="hidden" name="d" value="', $df->id(), '" />',
        '<input type="hidden" name="sesskey" value="', sesskey(), '" />';

    // multi action buttons
    echo '<div class="mdl-align">',
        'With selected: ',
        '&nbsp;&nbsp;<input type="submit" name="multiduplicate" value="', get_string('multiduplicate', 'dataform'), '" />',
        '&nbsp;&nbsp;',
        '<input type="submit" name="multidelete" value="', get_string('multidelete', 'dataform'), '" />',
        '</div>',
        '<br />';

    /// table headings
    $strname = get_string('fieldname','dataform');
    $strtype = get_string('type', 'dataform');
    $strdescription = get_string('description');
    $strorder = get_string('defaultsortorder', 'dataform');
    $strdir = get_string('defaultsortdir', 'dataform');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $selectallnone = '<input type="checkbox" '.
                        'onclick="inps=document.getElementsByTagName(\'input\');'.
                            'for (var i=0;i<inps.length;i++) {'.
                                'if (inps[i].type==\'checkbox\' && inps[i].name.search(\'fieldselector_\')!=-1){'.
                                    'inps[i].checked=this.checked;'.
                                '}'.
                            '}" />';

    $table->head = array($strname, $strtype, $strdescription, $strorder, $strdir, $stredit, $strdelete, $selectallnone);
    $table->align = array('left','left','left', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false);

    // parse dataform default sort
    if ($df->data->defaultsort) {
        if ($sortfields = unserialize($df->data->defaultsort)) {
            $sortfieldids = array_keys($sortfields);
        }
    }
    
    $orderoptions = array (1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
    $diroptions = array(0 => get_string('ascending', 'dataform'),
                        1 => get_string('descending', 'dataform'));
    
    foreach ($fields as $fieldid => $field) {
        $sortorder = $sortdir = 0;
        // check if field participates in default sort
        if (isset($sortfieldids)) {
            $insort = array_search($fieldid, $sortfieldids);
            if ($insort !== false) {
                $sortorder = $insort + 1;
                $sortdir =  $sortfields[$fieldid];
            }
        }
        
        // set fields table display
        if ($fieldid > 0) {    // user fields
            $fieldname = '<a href="field/field_edit.php?d='.$df->id(). '&amp;fid='.$fieldid.'&amp;sesskey='.sesskey().'">'. $field->name(). '</a>';
            $fieldedit = '<a href="field/field_edit.php?d='.$df->id(). '&amp;fid='.$fieldid.'&amp;sesskey='.sesskey().'">'.
                        '<img src="'.$CFG->pixpath.'/t/edit.gif" class="iconsmall" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>';
            $fielddelete = '<a href="fields.php?d='.$df->id().'&amp;delete='.$fieldid.'&amp;sesskey='.sesskey().'">'.
                        '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>';
            $fieldselector = '<input type="checkbox" name="fieldselector_'. $fieldid. '" />';
        } else {                // builtin field
            $fieldname = $field->name();
            $fieldedit = '-';
            $fielddelete = '-';
            $fieldselector = '-';
        }
        $fieldtype = $field->image().'&nbsp;'.get_string($field->type(), 'dataform');
        $fielddescription = shorten_text($field->field->description, 30);
        $fieldsortoption = choose_from_menu($orderoptions, 'defaultsort'. $fieldid, $sortorder, 'choose' , '', 0 , true);
        $fielddiroption = choose_from_menu($diroptions, 'defaultdir'. $fieldid, $sortdir, '', '', 0, true);

        
        $table->data[] = array(
            $fieldname,
            $fieldtype,
            $fielddescription,
            $fieldsortoption,
            $fielddiroption,
            $fieldedit,
            $fielddelete,
            $fieldselector
        );
    }
    
    print_table($table);
    echo '<br />',
        '<div class="mdl-align">',
        '<input type="submit" name="updatedefaultsort" value="', get_string('updatedefaultsort', 'dataform'), '" />',
        '</div>',
        '</form>';
}

// Finish the page
print_footer($df->course);

?>