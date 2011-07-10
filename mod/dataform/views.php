<?php  // $Id$

require_once('../../config.php');
require_once('mod_class.php');


$id         = optional_param('id', 0, PARAM_INT);            // course module id
$d          = optional_param('d', 0, PARAM_INT);             // dataform id
$vid        = optional_param('vid', 0 , PARAM_INT);          // update view id

// views list actions
$default    = optional_param('default', 0, PARAM_INT);  // id of view to default
$edit       = optional_param('edit', 0, PARAM_INT);     // id of view to edit
$visible    = optional_param('visible', 0, PARAM_SEQUENCE);     // ids (comma delimited) of views to hide/(show)/show
$hide       = optional_param('hide', 0, PARAM_SEQUENCE);     // ids (comma delimited) of views to hide
$delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to delete
$duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to duplicate
// TODO
$setfilter     = optional_param('setfilter', 0, PARAM_INT);  // id of view to filter

$confirm    = optional_param('confirm', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($d, $id);

require_capability('mod/dataform:managetemplates', $df->context);


$navigation = build_navigation('', $df->cm);
print_header_simple($df->name(), '', $navigation,
                    '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
                    navmenu($df->course, $df->cm), '', '');

print_heading(format_string($df->name()));

// DATA PROCESSING
if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/views.php') and confirm_sesskey()) {
    if (!empty($forminput->multiduplicate) or !empty($forminput->multidelete)) {
        $vids = array();
        foreach ($forminput as $name => $checked) {
            if (strpos($name, 'viewselector_') !== false) {
                if ($checked) {
                    $namearr = explode('_', $name);  // Second one is the view id                   
                    $vids[] = $namearr[1];
                }
            }
        }
        
        if ($vids) {
            if (!empty($forminput->multiduplicate)) {
                $duplicate = implode(',', $vids);
            } else if (!empty($forminput->multidelete)) {
                $delete = implode(',', $vids);        
            }
        }
    }
}

if ($visible and confirm_sesskey()) {    // set view's visibility
    $df->process_views('visible', $visible, true);    // confirmed by default

} else if ($hide and confirm_sesskey()) {  // hide any requested views
    $df->process_views('hide', $hide, $confirm);

} else if ($duplicate and confirm_sesskey()) {  // Duplicate any requested views
    $df->process_views('duplicate', $duplicate, $confirm);

} else if ($delete and confirm_sesskey()) { // Delete any requested views
    $df->process_views('delete', $delete, $confirm);

} else if ($default and confirm_sesskey()) {  // set view to default
    $df->process_views('default', $default, true);    // confirmed by default

} else if ($setfilter and confirm_sesskey()) {  // set view to default
    $df->process_views('filter', $setfilter, true);    // confirmed by default

}


// Print the tabs
$currenttab = 'views';
include('tabs.php');

// Notifications first
if (!$views = $df->get_views()) {
    notify(get_string('noviewsindataform','dataform'));  // nothing in database
    notify(get_string('pleaseaddsome','dataform', 'presets.php?d='.$df->id()));      // link to presets
} else if (empty($df->data->defaultview)) {
    notify(get_string('nodefaultview','dataform'));
}

// Display the view form jump list
$directories = get_list_of_plugins('mod/dataform/view/');
$menuview = array();
foreach ($directories as $directory){
    $menuview[$directory] = get_string('viewtype'. $directory, 'dataform');    //get from language files
}
asort($menuview);    //sort in alphabetical order

echo '<br />';
echo '<div class="fieldadd">';
echo '<label for="viewform_jump">'.get_string('viewcreate','dataform').'</label>&nbsp;';
popup_form($CFG->wwwroot.'/mod/dataform/view/view_edit.php?d='. $df->id().'&amp;sesskey='.
        sesskey().'&amp;type=', $menuview, 'viewform', '', 'choose');
helpbutton('views', get_string('addaview','dataform'), 'dataform');
echo '</div>';
echo '<br />';

// if there are views print admin style list of them
if ($views) {
    echo '<form id="viewslist" action="'.$CFG->wwwroot.'/mod/dataform/views.php" method="post">';
    echo '<input type="hidden" name="d" value="'.$df->id().'" />';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

    // multi action buttons
    echo '<div class="mdl-align">',
        'With selected: ',
        '&nbsp;&nbsp;<input type="submit" name="multiduplicate" value="', get_string('multiduplicate', 'dataform'), '" />',
        '&nbsp;&nbsp;',
        '<input type="submit" name="multidelete" value="', get_string('multidelete', 'dataform'), '" />',
        '</div>',
        '<br />';

    /// table headings
    $strviews = get_string('views', 'dataform');
    $strtype = get_string('type', 'dataform');
    $strdescription = get_string('description');
    $strvisible = get_string('visible');
    $strdefault = get_string('default');
    $strfilter = get_string('filter', 'dataform');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $selectallnone = '<input type="checkbox" '.
                        'onclick="inps=document.getElementsByTagName(\'input\');'.
                            'for (var i=0;i<inps.length;i++) {'.
                                'if (inps[i].type==\'checkbox\' && inps[i].name.search(\'viewselector_\')!=-1){'.
                                    'inps[i].checked=this.checked;'.
                                '}'.
                            '}" />';

    $strhide = get_string('hide');
    $strshow = get_string('show');
    $strreset =  get_string('reset');
    
    $filtersmenu = $df->filters_menu();
        
    $table->head = array($strviews, $strtype, $strdescription, $strvisible, $strdefault, $strfilter, $stredit, $strdelete, $selectallnone);
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false, false);
    
    foreach ($views as $view) {
        
        // prepare visibility
        $visibility = '<a href="views.php?d='. $df->id().'&amp;visible='.$view->view->id.'&amp;sesskey='.sesskey().'">';
        if ($visibile = $view->view->visible) {
            $visibility = ($visibile == 1 ? '(' : '').
                            $visibility. '<img src="'.$CFG->pixpath.'/t/hide.gif" class="iconsmall" alt="'. $strhide. '" title="'. $strhide. '" /></a>'.
                            ($visibile == 1 ? ')' : '');
        } else {
            $visibility .= '<img src="'.$CFG->pixpath.'/t/show.gif" class="iconsmall" alt="'. $strshow. '" title="'. $strshow. '" /></a>';
        }

        // prepare default view
        if ($view->view->id == $df->data->defaultview) {
            $defaultview ='<img src="'.$CFG->pixpath.'/t/clear.gif" class="iconsmall" alt="'. $strdefault. '" title="'. $strdefault. '" />';
        } else {
            $defaultview = '<a href="views.php?d='. $df->id().'&amp;default='.$view->view->id.'&amp;sesskey='.sesskey().'">Choose</a>';
        }
        
        // prepare view filter
        // TODO
        if ($view->filter() !== false) {
            if (!empty($filtersmenu)) {
                if ($view->filter() and !in_array($view->filter(), array_keys($filtersmenu))) {
                    $viewfilter = '<a href="views.php?d='. $df->id(). '&amp;setfilter='. $view->view->id. '&amp;fid=0&amp;sesskey='.sesskey().'">'.
                    '<img src="'.$CFG->pixpath.'/i/risk_xss.gif" class="iconsmall" alt="'. $strreset. '" title="'. $strreset. '" /></a>';
                } else {
                    $viewfilter = choose_from_menu($filtersmenu, '', $view->filter(), 'choose', 'location.href=\'views.php?d='. $df->id(). '&amp;setfilter='. $view->view->id. '&amp;fid=\'+this.selectedIndex+\'&amp;sesskey='.sesskey().'\'', 0, true);
                }
            } else {
                $viewfilter = get_string('filtersnonedefined', 'dataform');
            }
        } else {
            $viewfilter = '-';
        }
        
        
        //$table->add_data(array(
        $table->data[] = array(
            // name
            '<a href="view/view_edit.php?d='. $df->id().
                '&amp;vid='.$view->view->id.'&amp;sesskey='.sesskey().'">'.$view->view->name.'</a>',
            // type
            $view->image().'&nbsp;'.get_string('viewtype'. $view->type(), 'dataform'),
            // description
            shorten_text($view->view->description, 30),
            // visibility
            $visibility,
            // default
            $defaultview,
            // filter
            $viewfilter,
            // edit
            '<a href="view/view_edit.php?d='. $df->id().'&amp;vid='.$view->view->id.'&amp;sesskey='.sesskey().'">'.
            '<img src="'.$CFG->pixpath.'/t/edit.gif" class="ic
            onsmall" alt="'. $stredit. '" title="'. $stredit. '" /></a>',
            // delete
            '<a href="views.php?d='. $df->id().'&amp;delete='.$view->view->id.'&amp;sesskey='.sesskey().'">'.
            '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'. $strdelete. '" title="'. $strdelete. '" /></a>',
            //selector
            '<input type="checkbox" name="viewselector_'. $view->view->id. '" />'
       );
    }
    print_table($table);
    echo '<br />',
        '</div></form>';
}

// Finish the page
print_footer($df->course);


?>