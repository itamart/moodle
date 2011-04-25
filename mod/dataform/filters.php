<?php  // $Id$
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
require_once('lib.php');


$id         = optional_param('id', 0, PARAM_INT);            // course module id
$d          = optional_param('d', 0, PARAM_INT);             // dataform id
$fid        = optional_param('fid', 0 , PARAM_INT);          // update filter id

// filters list actions
$new        = optional_param('new', 0, PARAM_INT);     // new filter

$show       = optional_param('show', 0, PARAM_INT);     // filter show/hide flag
$hide       = optional_param('hide', 0, PARAM_INT);     // filter show/hide flag
//$default    = optional_param('default', 0, PARAM_INT);  // id of filter to default
$edit       = optional_param('edit', 0, PARAM_INT);     // id of filter to edit
$delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of filters to delete
$duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of filters to duplicate

$confirm    = optional_param('confirm', 0, PARAM_INT);    

// filter actions
$add        = optional_param('add', 0, PARAM_INT);      // add new filter
$update     = optional_param('update', 0, PARAM_INT);   // update filter
$cancel     = optional_param('cancel', '');

// Set a dataform object
$df = new dataform($d, $id);

require_login($df->course->id, false, $df->cm);
$df->context = get_context_instance(CONTEXT_MODULE, $df->cm->id);
require_capability('mod/dataform:managetemplates', $df->context);

// Print the browsing interface

$navigation = build_navigation('', $df->cm);
print_header_simple($df->name(), '', $navigation,
                    '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
                    navmenu($df->course, $df->cm), '', '');

print_heading(format_string($df->name()));

// DATA PROCESSING
if ($cancel) {
    $add = $update = 0;
}

// check for multi actions
if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/filters.php') and confirm_sesskey()) {
    if (!empty($forminput->multiduplicate) or !empty($forminput->multidelete)) {
        $fids = array();
        foreach ($forminput as $name => $checked) {
            if (strpos($name, 'filterselector_') !== false) {
                if ($checked) {
                    $namearr = explode('_', $name);  // Second one is the filter id                   
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

if ($duplicate and confirm_sesskey()) {  // Duplicate any requested filters
    $df->process_filters('duplicate', $duplicate, $confirm);

} else if ($delete and confirm_sesskey()) { // Delete any requested filters
    $df->process_filters('delete', $delete, $confirm);

} else if ($show and confirm_sesskey()) {    // set filter to visible
    $df->process_filters('show', $show, true);    // confirmed by default

} else if ($hide and confirm_sesskey()) {   // set filter to visible
    $df->process_filters('hide', $hide, true);    // confirmed by default

// add or update
} else if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/filters.php')) {

    if ($add and confirm_sesskey()) {    // add a new filter
        // Only add this filter if its name doesn't already exist
        if (($forminput->name == '') or $df->name_exists('filters', $forminput->name)) {
            $displaynoticebad = get_string('filterinvalidname','dataform');
        } else {
            $df->process_filters('add', 0, true);    // confirmed by default
        }

    } else if ($update and confirm_sesskey()) {   // update filter
        // Only update this filter if its name doesn't already exist
        if (($forminput->name == '') or $df->name_exists('filters', $forminput->name, $update)) {
            $displaynoticebad = get_string('filterinvalidname','dataform');
        } else {
            $df->process_filters('add', $update, true);    // confirmed by default
        }
    }
}

// Print the tabs
$currenttab = 'filters';
include('tabs.php');

if ($new && confirm_sesskey()) {    //  Open a new filter
    $df->display_filter_form();

} else if ($edit && confirm_sesskey()) {  // Edit existing filter
    $df->display_filter_form($edit);

} else {    
    // Notifications first
    if (!$filters = get_records('dataform_filters', 'dataid', $df->id())) {
        notify(get_string('filtersnoneindataform','dataform'));  // nothing in database
        notify(get_string('pleaseaddsome','dataform', 'preset.php?id='.$df->cm->id));      // link to presets
    }

    echo '<br />',
        '<div class="fieldadd">',
        '<a name="createfilter" href="', $CFG->wwwroot, '/mod/dataform/filters.php?d=', $df->id(), '&amp;sesskey=', sesskey(), '&amp;new=1">',
        get_string('filtercreate','dataform'), '</a>&nbsp;',
        helpbutton('filters', get_string('filteradd','dataform'), 'dataform'),
        '</div>',
        '<br />';

    // if there are filters print admin style list of them
    if ($filters) {
        echo '<form id="filterslist" action="', $CFG->wwwroot, '/mod/dataform/filters.php" method="post">',
            '<input type="hidden" name="d" value="', $df->id(), '" />',
            '<input type="hidden" name="sesskey" value="', sesskey(), '" />';

        // multi action buttons
        echo '<div class="mdl-align">',
            'With selected: ',
            '<input type="submit" name="multiduplicate" value="', get_string('multiduplicate', 'dataform'), '" />',
            '&nbsp;&nbsp;',
            '<input type="submit" name="multidelete" value="', get_string('multidelete', 'dataform'), '" />',        
            '</div>',
            '<br />';

        // table headings
        $strfilters = get_string('name');
        $strdescription = get_string('description');
        $strperpage = get_string('filterperpage', 'dataform');
        $strcustomsort = get_string('filtercustomsort', 'dataform');
        $strsimplesearch = get_string('filtersimplesearch', 'dataform');
        $strcustomsearch = get_string('filtercustomsearch', 'dataform');
        $strvisible = get_string('visible');
        $stredit = get_string('edit');
        $strdelete = get_string('delete');
        $selectallnone = '<input type="checkbox" '.
                            'onclick="inps=document.getElementsByTagName(\'input\');'.
                                'for (var i=0;i<inps.length;i++) {'.
                                    'if (inps[i].type==\'checkbox\' && inps[i].name.search(\'filterselector_\')!=-1){'.
                                        'inps[i].checked=this.checked;'.
                                    '}'.
                                '}" />';

        $table->head = array($strfilters, $strdescription, $strperpage, $strcustomsort, 
                            $strsimplesearch, $strcustomsearch, $strvisible, $stredit, 
                            $strdelete, $selectallnone);
        $table->align = array('left', 'left', 'center', 'left', 'left', 'left', 'center', 'center', 'center', 'center');
        $table->wrap = array(false, false, false, false, false, false, false, false, false, false);
        
        foreach ($filters as $filter) {
            if ($filter->visible) {
                $visible = '<a href="filters.php?d='. $df->id().'&amp;hide='.$filter->id.'&amp;sesskey='.sesskey().'">'.
                            '<img src="'.$CFG->pixpath.'/t/hide.gif" class="iconsmall" alt="'. get_string('hide'). '" title="'. get_string('hide'). '" /></a>';
                $class = "";
            } else {
                $visible = '<a href="filters.php?d='. $df->id().'&amp;show='.$filter->id.'&amp;sesskey='.sesskey().'">'.
                            '<img src="'.$CFG->pixpath.'/t/show.gif" class="iconsmall" alt="'. get_string('show'). '" title="'. get_string('show'). '" /></a>';
                $class = " class=\"dimmed_text\"";
            }
            
            $customsort = $customsearch = '';
            
            // parse custom settings
            if ($filter->customsort or $filter->customsearch) {
                if ($filter->customsort) {
                    $sortfields = explode(',', $filter->customsort);
                    foreach ($sortfields as &$sf) {
                        $sf = explode(' ', $sf);
                    }
                }
                
                // parse filter search settings
                if ($filter->customsearch) {
                    $searchfields = explode(',', $filter->customsearch);
                    foreach ($searchfields as &$sf) {
                        $sf = explode('|||', $sf);
                    }
                }

                // get fields objects
                $fields = $df->get_fields();
                
                if (isset($sortfields) and !empty($sortfields)) {
                    foreach ($sortfields as $sortfield) {
                        // check if field participates in default sort
                        foreach ($fields as $field) {
                            if ($field->field->id and ($field->field->id == $sortfield[0])) {
                                $customsort .= '<img src="'.$CFG->pixpath.'/t/'. ($sortfield[1] ? 'down' : 'up'). '.gif" class="iconsmall" alt="'. ($sortfield[1] ? 'Descending' : 'Ascending'). '" title="'. ($sortfield[1] ? 'Descending' : 'Ascending'). '" />&nbsp;';
                                $customsort .= $field->field->name. '<br />';
                                break;
                            }
                        }
                    }
                }
            
                if (isset($searchfields) and !empty($searchfields)) {
                    foreach ($searchfields as $searchfield) {
                        // check if field participates in search
                        foreach ($fields as $field) {
                            if ($field->field->id and ($field->field->id == $searchfield[0])) {
                                $customsearch .= '<b>'. $field->field->name. '</b>';
                                $customsearch .= ':&nbsp;'. $searchfield[1]. '<br />';
                                break;
                            }
                        }
                    }
                }
            }

            $table->data[] = array(
                // name
                '<a'. $class. ' href="filters.php?d='. $df->id(). '&amp;edit='. $filter->id. '&amp;sesskey='. sesskey(). '">'. $filter->name. '</a>',
                // description
                shorten_text($filter->description, 30),
                // per page
                $filter->perpage,
                // custom sort
                $customsort,
                // per page
                $filter->search,
                // per page
                $customsearch,
                // visibility
                $visible,
                // edit
                '<a href="filters.php?d='. $df->id().'&amp;edit='.$filter->id.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/t/edit.gif" class="iconsmall" alt="'. $stredit. '" title="'. $stredit. '" /></a>',
                // delete
                '<a href="filters.php?d='. $df->id().'&amp;delete='.$filter->id.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'. $strdelete. '" title="'. $strdelete. '" /></a>',
                //selector
                '<input type="checkbox" name="filterselector_'. $filter->id. '" />'
           );
        }
        print_table($table);
        echo '<br />',
            '</div></form>';
    }
}

// Finish the page
print_footer($df->course);


?>