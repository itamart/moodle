<?php // $Id$

require_once('../../config.php');
require_once('mod_class.php');

$d          = optional_param('d', 0, PARAM_INT);             // dataform id
$id         = optional_param('id', 0, PARAM_INT);            // course module id

$new =      optional_param('new', 0, PARAM_INT);   // save current preset

// presets list actions
$apply =    optional_param('apply', '', PARAM_PATH);  // path of preset to apply
$applymap = optional_param('applymap', '', PARAM_PATH);  // path of preset to apply with mapping
$map =      optional_param('map', 0, PARAM_BOOL);  // map new preset fields to old fields
$delete =   optional_param('delete', '', PARAM_PATH);   // path of preset to delete
$share =    optional_param('share', 0, PARAM_PATH);     // path of preset to share

$confirm    = optional_param('confirm', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($d, $id);

require_capability('mod/dataform:managetemplates', $df->context);


// DATA PROCESSING
if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/presets.php') and confirm_sesskey()) {
    // multi actions
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
    
    // field mapping for preset application
    if ($map) {
        foreach ($forminput as $name => $newfieldid) {
            if (strpos($name, 'field_') !== false) {
                $namearr = explode('_', $name);  
                $oldfieldid = $namearr[1];  // Second one is the old field id
                $oldfieldtype = $namearr[2];  // Third is the old field type
                $oldfield = $df->get_field($oldfieldtype);
                $oldfield->field->id = $oldfieldid;
                if ($newfieldid) {
                    $oldfield->transfer_content($newfieldid);
                } else {
                    $oldfield->delete_content();
                }
            }
        }
    }
}

// print header now b/c we may need it for mapping
$navigation = build_navigation('', $df->cm);
print_header_simple($df->name(), '', $navigation,
                    '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
                    navmenu($df->course, $df->cm), '', '');

print_heading(format_string($df->name()));


if ($new and confirm_sesskey()) {    // save current preset
    $df->backup('preset');    // confirmed by default

} else if ($apply and confirm_sesskey()) {    // apply preset
    $df->apply_preset($apply);    // apply selected preset

} else if ($applymap and confirm_sesskey()) {
    $df->apply_preset($applymap, true);    // apply selected preset with mapping
    
    // mapping prints mapping form
    // so finish the page and exit
    print_footer($df->course);
    exit(0);

} else if ($share and confirm_sesskey()) {  // share selected presets
    $df->share_preset($share);

} else if ($delete and confirm_sesskey()) { // delete selected presets
    $df->delete_preset($delete);

}

// Print the tabs
$currenttab = 'presets';
include('tabs.php');

// get local presets (from presets folder in the course files)
$localpresets = $df->get_local_presets();

// get shared presets
$sharedpresets = $df->get_shared_presets();

// Notifications first
if (!$localpresets and !$sharedpresets) {
    notify(get_string('presetnoneavailable','dataform'));  // no available prests to display
}

// TODO
echo '<br />';
// save current dataform to local preset form
echo '<div class="fieldadd">';
echo '<a href="presets.php?d='. $df->id(). '&amp;new=1&amp;sesskey='.sesskey().'">'.get_string('presetaddfromdataform','dataform').'</a>';
helpbutton('presets', get_string('presetaddfromdataform','dataform'), 'dataform');
echo '<br /><br />';
// upload preset file
echo '<a href="javascript:void(0);" onclick="return openpopup(\'/files/index.php?id='. $df->course->id. '&amp;wdir=/moddata/dataform/presets&amp;choose=uploadpreset.file\', \'coursefiles\', \'menubar=0,location=0,scrollbars,resizable,width=750,height=500\', 0);">'. get_string('presetaddfromfile', 'dataform'). '</a> (<a href="presets.php?d='. $df->id().'">'. get_string('presetrefreshlist', 'dataform'). '</a>)';
helpbutton('presets', get_string('presetaddfromfile','dataform'), 'dataform');
echo '</div>';
echo '<br />';

// if there are presets print admin style list of them
if ($localpresets or $sharedpresets) {

    // prepare to make file links
    require_once($CFG->libdir.'/filelib.php');

    /// table headings
    $strname = get_string('name');
    $strdescription = get_string('description');
    $strscreenshot = get_string('screenshot');
    $strapply = get_string('presetapply', 'dataform');
    $strmap = get_string('presetmap', 'dataform');
    $strdownload = get_string('download', 'dataform');
    $strdelete = get_string('delete');
    $strshare = get_string('presetshare', 'dataform');

    $table->head = array($strname, $strdescription, $strscreenshot, $strapply, $strapply. "\n($strmap)", $strdownload, $strdelete, $strshare);
    $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false);

    // print local presets
    if ($localpresets) {
        echo '<h3 style="text-align:center;">'. get_string('presetavailableincourse', 'dataform'). '</h3>';

        foreach ($localpresets as $preset) {

            // prepare screenshot
            $presetscreenshot = '';
            if ($preset->screenshot) {
                $presetscreenshot = '<img width="150" class="presetscreenshot" src="'. $preset->screenshot. '" alt="'. get_string('screenshot'). '" />';
            }

            // preset path without file ext
            $presetpath = $df->course->id. '/moddata/dataform/presets/'. $preset->name;
            // preset url for download
            $preseturl = get_file_url($df->course->id. '/moddata/dataform/presets'). '/'. $preset->name. '.zip';

            $table->data[] = array(
                // name
                $preset->name,
                // description  $preset->description,
                '',
                // screenshot
                $presetscreenshot,
                // apply
                '<a href="presets.php?d='. $df->id().'&amp;apply='.$presetpath.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/t/switch_whole.gif" class="iconsmall" alt="'. $strapply. '" title="'. $strapply. '" /></a>',
                // apply with mapping
                '<a href="presets.php?d='. $df->id().'&amp;applymap='.$presetpath.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/t/switch_plus.gif" class="iconsmall" alt="'. $strapply. '" title="'. $strapply. '" /></a>',
                // download
                '<a href="'. $preseturl. '">'.
                '<img src="'.$CFG->pixpath.'/i/backup.gif" class="iconsmall" alt="'. $strdownload. '" title="'. $strdownload. '" /></a>',
                // delete
                '<a href="presets.php?d='. $df->id().'&amp;delete='.$presetpath.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'. $strdelete. '" title="'. $strdelete. '" /></a>',
                // share
                '<a href="presets.php?d='. $df->id().'&amp;share='.$presetpath.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/i/group.gif" class="iconsmall" alt="'. $strshare. '" title="'. $strshare. '" /></a>'
           );
        }
        print_table($table);
        echo '<br />';
    }

    // print shared presets
    if ($sharedpresets) {
        echo '<h3 style="text-align:center;">'. get_string('presetavailableinsite', 'dataform'). '</h3>';
        $table->data = array();

        foreach ($sharedpresets as $preset) {

            // prepare screenshot
            $presetscreenshot = '';
            if ($preset->screenshot) {
                $presetscreenshot = '<img width="150" class="presetscreenshot" src="'. $preset->screenshot. '" alt="'. get_string('screenshot'). '" />';
            }

            // preset path for download
            $presetpath = 'dataform/presets/'. $preset->userid. '/'. $preset->name;
            //$preseturl = get_file_url('dataform/presets'). '/'. $preset->userid. '/'. $preset->name. '.zip';

            // prepare delete link
            $dellink = '';
            if ($preset->userid > 0 and ($preset->userid == $USER->id or has_capability('mod/dataform:manageuserpresets', $df->context))) {
                $dellink = '<a href="presets.php?d='. $df->id().'&amp;shared=1&amp;delete='.$presetpath.'&amp;sesskey='.sesskey().'">'.
                            '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'. $strdelete. '" title="'. $strdelete. '" /></a>';
            }

            $table->data[] = array(
                // name
                $preset->name,
                // description   $preset->description,
                '',
                // screenshot
                $presetscreenshot,
                // apply
                '<a href="presets.php?d='. $df->id().'&amp;shared=1&amp;apply='.$presetpath.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/t/switch_whole.gif" class="iconsmall" alt="'. $strapply. '" title="'. $strapply. '" /></a>',
                // apply with mapping
                '<a href="presets.php?d='. $df->id().'&amp;shared=1&amp;applymap='.$presetpath.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$CFG->pixpath.'/t/switch_plus.gif" class="iconsmall" alt="'. $strapply. '" title="'. $strapply. '" /></a>',
                // download
                '<a href="file.php?d='. $df->id().'&amp;file='.$presetpath.'.zip&amp;sesskey='.sesskey().'">'.
                //'<a href="'. $preseturl. '">'.
                '<img src="'.$CFG->pixpath.'/i/backup.gif" class="iconsmall" alt="'. $strdownload. '" title="'. $strdownload. '" /></a>',
                // delete
                $dellink,
                // share
                ''
           );
        }
        print_table($table);
        echo '<br />';
    }
}

// Finish the page
print_footer($df->course);

?>