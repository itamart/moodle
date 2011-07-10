<?php  // $Id$

require_once('../../config.php');
require_once('mod_class.php');
require_once($CFG->libdir.'/blocklib.php');
require_once("$CFG->libdir/rsslib.php");

require_once('pagelib.php');

// One of these is necessary!
$d = optional_param('d', 0, PARAM_INT);             // dataform id
$id = optional_param('id', 0, PARAM_INT);           // course module id
$rid = optional_param('rid', 0, PARAM_INT);         // record id

$edit = optional_param('edit', -1, PARAM_BOOL);     // teacher editing mode     
$view = optional_param('view', 0, PARAM_INT);       // current view id
$filter = optional_param('filter', 0, PARAM_INT);     // current filter (-1 for user filter)
$userpref = optional_param('userpref', 0, PARAM_BOOL);     // set user filtering preferences

// These can be added to perform an action on entries
$editentries = optional_param('editentries', 0, PARAM_SEQUENCE);        // edit entries (all) or by record ids (comma delimited rids)     

$new = optional_param('new', 0, PARAM_BOOL);               // open new entry form
$add = optional_param('add', 0, PARAM_BOOL);          // add entries (all) or by record ids (comma delimited rids) 
$update = optional_param('update', '', PARAM_SEQUENCE);    // update entries (all) or by record ids (comma delimited rids) 
$duplicate = optional_param('duplicate', '', PARAM_SEQUENCE);    // duplicate entries (all) or by record ids (comma delimited rids) 
$delete = optional_param('delete', '', PARAM_SEQUENCE);    // delete entries (all) or by record ids (comma delimited rids)
$approve = optional_param('approve', '', PARAM_SEQUENCE);  // approve entries (all) or by record ids (comma delimited rids)
$disapprove = optional_param('disapprove', '', PARAM_SEQUENCE);  // disapprove entries (all) or by record ids (comma delimited rids)
$confirm = optional_param('confirm',0,PARAM_INT);

$addcomment = optional_param('addcomment', 0, PARAM_BOOL);  // disapprove entries (all) or by record ids (comma delimited rids)
$deletecomment = optional_param('deletecomment', 0, PARAM_INT);  // disapprove entries (all) or by record ids (comma delimited rids)

// Set a dataform object with guest autologin
$df = new dataform($d, $id, true);

require_capability('mod/dataform:viewentry', $df->context);

$df->get_ready_to_browse();  // may redirect if for some reason cannot browse

// TODO    
// set user filter preferences
if ($userpref == 1) {
    set_user_preference('dataform_'. $df->id(). '_perpage', optional_param('userperpage', get_user_preferences('dataform_'. $df->id(). '_perpage', 0), PARAM_INT));
    set_user_preference('dataform_'. $df->id(). '_groupby', optional_param('usergroupby', get_user_preferences('dataform_'. $df->id(). '_groupby', 0), PARAM_INT));
    set_user_preference('dataform_'. $df->id(). '_search', optional_param('usersearch', get_user_preferences('dataform_'. $df->id(). '_search', ''), PARAM_NOTAGS));
    set_user_preference('dataform_'. $df->id(). '_customsort', optional_param('usercustomsort', get_user_preferences('dataform_'. $df->id(). '_customsort', ''), PARAM_RAW));
    set_user_preference('dataform_'. $df->id(). '_customsearch', optional_param('usercustomsearch', get_user_preferences('dataform_'. $df->id(). '_customsearch', ''), PARAM_RAW));
} else if ($userpref == -1) {
    set_user_preference('dataform_'. $df->id(). '_perpage', 0);
    set_user_preference('dataform_'. $df->id(). '_groupby', 0);
    set_user_preference('dataform_'. $df->id(). '_search', '');
    set_user_preference('dataform_'. $df->id(). '_customsort', '');
    set_user_preference('dataform_'. $df->id(). '_customsearch', '');
}

// get the current view
if (!$currentview = $df->get_view_from_id($view)) {
    // TODO: get string
    error('No views were set for this activity.');
}

// Data processing
if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/view.php') and confirm_sesskey()) {
    // check for multi actions
    if (isset($forminput->multiduplicate) or isset($forminput->multiedit) or isset($forminput->multidelete) or isset($forminput->multiapprove)) {
        $rids = array();
        foreach ($forminput as $name => $checked) {
            if (strpos($name, 'selector_') !== false) {
                if ($checked) {
                    $namearr = explode('_', $name);  // Second one is the field id                   
                    $rids[] = $namearr[1];
                }
            }
        }
        
        if ($rids) {
            if (isset($forminput->multiduplicate)) {
                $duplicate = implode(',', $rids);
            } else if (isset($forminput->multiedit)) {
                $editentries = implode(',', $rids);
            } else if (isset($forminput->multidelete)) {
                $delete = implode(',', $rids);        
            } else if (isset($forminput->multiapprove)) {
                $approve = implode(',', $rids);        
            }
        }

    } else if (!empty($forminput->cancel)) {
        $add = $update = '';

    }
    
}

// if we got this far then it's ok to process user requests and view entries
// Initialize $PAGE, compute blocks
$PAGE = page_create_instance($df->id());

if (($edit != -1) and $PAGE->user_allowed_editing()) {
    $USER->editing = $edit;
}

// RSS and CSS and JS meta
$meta = '';
if (!empty($CFG->enablerssfeeds) && !empty($CFG->dataform_enablerssfeeds) && $df->data->rssarticles > 0) {
    $rsspath = rss_get_url($df->course->id, $USER->id, 'dataform', $df->id());
    $meta .= '<link rel="alternate" type="application/rss+xml" ';
    $meta .= 'title ="'. format_string($df->course->shortname) .': %fullname%" href="'.$rsspath.'" />';
}
if ($df->data->css) {
    $meta .= '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/dataform/css.php?d='.$df->id().'" /> ';
}
if ($df->data->js) {
    $meta .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/dataform/js.php?d='.$df->id().'"></script>';
}

// Print the page header
$PAGE->print_header($df->course->shortname.': %fullname%', '', $meta);

// Check to see if groups are being used here
$returnurl = $CFG->wwwroot . '/mod/dataform/view.php?d='. $df->id(). '&amp;filter='. $filter. '&amp;';
groups_print_activity_menu($df->cm, $returnurl);

print_heading(format_string($df->name()));
// TODO: explore letting view decide whether to print rsslink and intro
$df->print_rsslink();
// TODO: allow to sessionally close intro
$df->print_intro();

// Prepare open a new entry form
if ($new and confirm_sesskey()) {
    $editentries = -1;        
// Add a new entry
} else if ($add and confirm_sesskey()) {
    $df->process_entries('add', 0, true);        
// Duplicate any requested entries
} else if ($duplicate and confirm_sesskey()) {
    $df->process_entries('duplicate', $duplicate, $confirm);        
// Update any requested entries
} else if ($update and confirm_sesskey()) {
    $df->process_entries('update', $update, true);  // confirmed by default
// Delete any requested entries
} else if ($delete and confirm_sesskey()) {
    $df->process_entries('delete', $delete, $confirm);        
// Approve any requested entries
} else if ($approve and confirm_sesskey()) {
    $df->process_entries('approve', $approve, true);        
// Approve any requested entries
} else if ($disapprove and confirm_sesskey()) {
    $df->process_entries('disapprove', $disapprove, true);        
// Add comment
} else if ($addcomment and confirm_sesskey()) {
    $df->add_comment();        
// Delete comment
} else if ($deletecomment and confirm_sesskey()) {
    $df->delete_comment($deletecomment);        
}

// Print the tabs
$currenttab = 'browse';
include('tabs.php');

// TODO:
echo '<table id="layout-table">';
echo '<tr>';
// Print left side blocks if any
$df->print_blocks($PAGE, BLOCK_POS_LEFT);

echo '<td id="middle-column">';
print_container_start();
$currentview->display_view($editentries);
print_container_end();
echo '</td>';

// Print right side blocks if any
$df->print_blocks($PAGE, BLOCK_POS_RIGHT);
echo '</tr>';

echo '</table>';

print_footer($df->course);
?>