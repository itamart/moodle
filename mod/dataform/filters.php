<?php  // $Id$

require_once('../../config.php');
require_once('mod_class.php');


$id         = optional_param('id', 0, PARAM_INT);            // course module id
$d          = optional_param('d', 0, PARAM_INT);             // dataform id
$fid        = optional_param('fid', 0 , PARAM_INT);          // update filter id

// filters list actions
$new        = optional_param('new', 0, PARAM_INT);     // new filter

$show       = optional_param('show', 0, PARAM_INT);     // filter show/hide flag
$hide       = optional_param('hide', 0, PARAM_INT);     // filter show/hide flag
$edit       = optional_param('edit', 0, PARAM_INT);     // id of filter to edit
$delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of filters to delete
$duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of filters to duplicate

$confirm    = optional_param('confirm', 0, PARAM_INT);    

// filter actions
$update     = optional_param('update', 0, PARAM_INT);   // update filter
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

// Print the tabs
$currenttab = 'filters';
include('tabs.php');

// DATA PROCESSING
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

} else if ($update and confirm_sesskey()) {  // Add/update a new filter
    $df->process_filters('update', $fid, true);
}

if ($new and confirm_sesskey()) {    //  Edit a new filter
    $filter = $df->get_filter_from_id();
    $df->display_filter_form($filter);

} else if ($edit and confirm_sesskey()) {  // Edit existing filter
    $filter = $df->get_filter_from_id($edit);
    $df->display_filter_form($filter);

} else {    
    // Notifications first
    if (!$filters = get_records('dataform_filters', 'dataid', $df->id())) {
        notify(get_string('filtersnoneindataform','dataform'));  // nothing in database
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

        $table->head = array($strfilters, $strdescription, $strperpage, 
                            $strcustomsort, $strcustomsearch, $strvisible, 
                            $stredit, $strdelete, $selectallnone);
        $table->align = array('left', 'left', 'center', 'left', 'left', 'center', 'center', 'center', 'center');
        $table->wrap = array(false, false, false, false, false, false, false, false, false);
        
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
            
            $searchoptions = $filter->search;
            
            // parse custom settings
            if ($filter->customsort or $filter->customsearch) {
                // parse filter sort settings
                $sortfields = array();
                if ($filter->customsort) {
                    $sortfields = unserialize($filter->customsort);
                }
                
                // parse filter search settings
                $searchfields = array();
                if ($filter->customsearch) {
                    $searchfields = unserialize($filter->customsearch);
                }

                // get fields objects
                $fields = $df->get_fields();
                
                if ($sortfields) {
                    foreach ($sortfields as $sortieid => $sortdir) {
                        // check if field participates in default sort
                        $sortoptions .= '<img src="'.$CFG->pixpath.'/t/'. ($sortdir ? 'down' : 'up'). '.gif" class="iconsmall" alt="'. ($sortdir ? 'Descending' : 'Ascending'). '" title="'. ($sortdir ? 'Descending' : 'Ascending'). '" />&nbsp;';
                        $sortoptions .= $fields[$sortieid]->field->name. '<br />';
                    }
                } else {
                    $sortoptions = '---';
                }
            
                if ($searchfields) {
                    $searcharr = array();
                    foreach ($searchfields as $fieldid => $searchfield) {
                        $fieldoptions = array();
                        if (isset($searchfield['AND']) and $searchfield['AND']) {
                            //$andoptions = array_map("$fields[$fieldid]->format_search_value", $searchfield['AND']);
                            $options = array();
                            foreach ($searchfield['AND'] as $option) {
                                if ($option) {
                                    $options[] = $fields[$fieldid]->format_search_value($option);
                                }
                            }
                            $fieldoptions[] = 'AND <b>'. $fields[$fieldid]->field->name. '</b>:'. implode(',', $options);
                        }
                        if (isset($searchfield['OR']) and $searchfield['OR']) {
                            //$oroptions = array_map("$fields[$fieldid]->format_search_value", $searchfield['OR']);
                            $options = array();
                            foreach ($searchfield['OR'] as $option) {
                                if ($option) {
                                    $options[] = $fields[$fieldid]->format_search_value($option);
                                }
                            }
                            $fieldoptions[] = 'OR <b>'. $fields[$fieldid]->field->name. '</b>:'. implode(',', $options);
                        }
                        if ($fieldoptions) {
                            $searcharr[] = implode('<br />', $fieldoptions);
                        }
                    }
                    if ($searcharr) {
                        $searchoptions = implode('<br />', $searcharr);
                    }
                } else {
                    $searchoptions = '---';
                }
            }

            $table->data[] = array(
                // name
                '<a'. $class. ' href="filters.php?d='. $df->id(). '&amp;edit='. $filter->id. '&amp;sesskey='. sesskey(). '">'. $filter->name. '</a>',
                // description
                shorten_text($filter->description, 30),
                // per page
                $filter->perpage,
                // sort options
                $sortoptions,
                // search options
                $searchoptions,
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