<?php  // $Id$

require_once('../../config.php');
require_once('lib.php');

global $CFG;

//you'll process some page parameters at the top here and get the info about
//what instance of your module and what course you're in etc. Make sure you
//include hidden variable in your forms which have their defaults set in set_data
//which pass these variables from page to page
  
$d          = required_param('d', PARAM_INT);    // dataform ID

$type       = optional_param('type','' ,PARAM_ALPHA);   // type of a new view to edit
$vid        = optional_param('vid',0 ,PARAM_INT);       // view id to edit
$default    = optional_param('default',0 ,PARAM_INT);   // 
$switcheditor    = optional_param('switcheditor',0 ,PARAM_INT);   // 

// Set a dataform object
$df = new dataform($d);

require_login($df->course->id, false, $df->cm);
require_capability('mod/dataform:managetemplates', $df->context);

if ($vid) {
    $view = $df->get_view_from_id($vid);
} else if ($type) {
    $view = $df->get_view($type);
    $view->generate_default_view();
}

if ($default) {
    $view->generate_default_view();
}

if ($switcheditor) {
    $view->editor = $view->editor ? 0 : 1;
    $SESSION->dataform_use_editor = $view->editor;
    //$view->switch_editor();
}

require_once($CFG->dirroot. '/mod/dataform/view/'. $view->type(). '/view_form.php');

$mform = $view->get_form();
//default 'action' for form is strip_querystring(qualified_me())

if ($mform->is_cancelled()){
    if ($d) {
        redirect($CFG->wwwroot.'/mod/dataform/views.php?d='. $d);
    }

// no submit buttons: reset to default, switch editor    
} else if ($mform->no_submit_button_pressed()) {
    $resettodefault = optional_param('resetdefaultbutton', '');
    $switcheditor = optional_param('switcheditorbutton', '');

    if ($resettodefault) {   // reset view to default
        // TODO is this the best way?
        redirect($CFG->wwwroot.'/mod/dataform/view_edit.php?d='. $d. '&amp;vid='. $vid. '&amp;type='. $type. '&amp;default=1');
        
    } else if ($switcheditor) {    // switch editor in the view design
        // TODO is this the best way?
        redirect($CFG->wwwroot.'/mod/dataform/view_edit.php?d='. $d. '&amp;vid='. $vid. '&amp;type='. $type. '&amp;switcheditor=1');
    }
    

// process validated    
} else if ($fromform = $mform->get_data()) { 

    if (!$vid) {    // add new view
        // Check for arrays and convert to a comma-delimited string
        $df->convert_arrays_to_strings($fromform);
        $view->insert_view($fromform);

        // TODO: default view
        
        add_to_log($df->course->id, 'dataform', 'views add',
                   'view_edit.php?d='. $df->id(), '', $df->cm->id);

        $displaynoticegood = get_string('viewadded','dataform');
    } else {   // update view

        // Check for arrays and convert to a comma-delimited string
        $df->convert_arrays_to_strings($fromform);
        
        $view->update_view($fromform);

        $dataid = $df->id();
        add_to_log($df->course->id, 'dataform', 'views update',
                   'views.php?d='. $df->id(). '&amp;view=', $vid, $df->cm->id);

        $displaynoticegood = get_string('viewupdated','dataform');
    }
    
    // go back to views list
    redirect($CFG->wwwroot.'/mod/dataform/views.php?d='. $d);
    
}

// the first display of the form
// or the form is submitted but the data doesn't validate and the form should be redisplayed

// For the javascript for inserting view tags: initialise the default textarea to 'param1'

$bodytag = 'onload="';
foreach ($view->editors() as $editor) {
    $editorobj = 'editor_'.md5($editor);
    $bodytag .= 'if (typeof('.$editorobj.') != \'undefined\') { editor_'. $editor. ' = '.$editorobj.'; } ';
    $bodytag .= 'else { editor_'. $editor. ' = document.getElementById(\'id_'. $editor. '\'); }';
}
$bodytag .= '" ';

// Javascript to insert the field tags into the textarea.
$meta = '<script type="text/javascript">'."\n";
$meta .= '//<![CDATA['."\n";
$meta .= 'function insert_field_tags(selectlist, editor) {';
$meta .= '  if (typeof(editor) != \'undefined\' && editor._editMode == \'wysiwyg\') {';
    // HTMLArea-specific
$meta .= '     editor.insertHTML(selectlist.options[selectlist.selectedIndex].value); '; 
$meta .= '  } else {';
    // For inserting when in HTMLArea code view or for normal textareas
$meta .= '     insertAtCursor(editor, selectlist.options[selectlist.selectedIndex].value);';   
$meta .= '  }'."\n";
$meta .= '}'."\n";
$meta .= '//]]>'."\n";
$meta .= '</script>'."\n";

// TODO: set $streditinga to new view or view name
$streditinga = $view->view->name;
$navigation = build_navigation('', $df->cm);
print_header_simple($streditinga, '', $navigation, $mform->focus(), $meta, false, '', '', '', $bodytag);

//call to print_heading_with_help or print_heading?
print_heading(format_string($view->view->name));

// put data you want to fill out in the form into array $toform here
$toform = $view->view;
   
// then set and display
$mform->set_data($toform);

$mform->display();
print_footer($course);

?>