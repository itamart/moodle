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
require_once($CFG->libdir.'/uploadlib.php');

require_login();

$d              = optional_param('d', 0, PARAM_INT);   // dataform id
$id             = optional_param('id', 0, PARAM_INT);  // course module id
$fielddelimiter = optional_param('fielddelimiter', ',', PARAM_CLEANHTML); // characters used as field delimiters for csv file import
$fieldenclosure = optional_param('fieldenclosure', '', PARAM_CLEANHTML);   // characters used as record delimiters for csv file import

// Set a dataform object
$df = new dataform($d, $id);

require_login($df->course, false, $df->cm);
$df->context = get_context_instance(CONTEXT_MODULE, $df->cm->id);
require_capability('mod/dataform:manageentries', $df->context);

// Print the page header
$strdata = get_string('modulenameplural','dataform');

$navigation = build_navigation('', $df->cm);
print_header_simple($df->name(), "", $navigation, "", "", true, "", navmenu($df->course));
print_heading(format_string($df->name()));

// Print the tabs
$currenttab = 'import';
include('tabs.php');

// Process incoming upload request
if ($import = data_submitted($CFG->wwwroot.'/mod/dataform/import.php') and confirm_sesskey()) {
    $recordsadded = 0;

    if (!empty($import->import)) {  // Upload                
        $text = '';
        $fp = NULL;
        
        if (!empty($import->csvfile)) { // Upload from file
            $um = new upload_manager('csvfile', false, false, null, false, 0);

            if ($um->preprocess_files()) {
                $filename = $um->files['csvfile']['tmp_name'];

                // Large files are likely to take their time and memory. Let PHP know
                // that we'll take longer, and that the process should be recycled soon
                // to free up memory.
                @set_time_limit(0);
                @raise_memory_limit("96M");
                if (function_exists('apache_child_terminate')) {
                    @apache_child_terminate();
                }

                // Fix mac/dos newlines and clean BOM
                // TODO: Switch to cvslib when possible
                $textlib = textlib_get_instance();
                $text = my_file_get_contents($filename);
                $text = preg_replace('!\r\n?!',"\n",$text);
                $text = $textlib->trim_utf8_bom($text); // remove Unicode BOM from first line
                $fp = fopen($filename, "w");
                fwrite($fp, $text);
                fclose($fp);
                
                $fp = fopen($filename, "r");
                // error('get_records_csv failed to open '.$filename);
                if (!$csvrecords = dataform_get_records_csv($fp, $fielddelimiter, $fieldenclosure)) {
                    print_error('csvfailed','dataform',"{$CFG->wwwroot}/mod/dataform/import.php?d={$df->id()}");
                }
                fclose($fp);
            }
            
        } else if (!empty($import->csvtext)) { // Upload from text
            $textlib = textlib_get_instance();
            $text = $import->csvtext;
            $text = preg_replace('!\r\n?!',"\n",$text);
            $text = $textlib->trim_utf8_bom($text); // remove Unicode BOM from first line
            $fp = tmpfile();
            fwrite($fp, $text);
            fseek($fp, 0);
            if (!$csvrecords = dataform_get_records_csv($fp, $fielddelimiter, $fieldenclosure)) {
                print_error('csvfailed','dataform',"{$CFG->wwwroot}/mod/dataform/import.php?d={$df->id()}");
            }
            fclose($fp);
        }

        if ($csvrecords) {
            //$db->debug = true;
            $fieldnames = array_shift($csvrecords);

            // check the fieldnames are valid
            $fields = get_records('dataform_fields', 'dataid', $df->id(), '', 'name, id, type');
            $errorfield = '';
            foreach ($fieldnames as $name) {
                if (!isset($fields[$name])) {
                    $errorfield .= "'$name' ";
                }
            }

            if (!empty($errorfield)) {
                print_error('fieldnotmatched','dataform',"{$CFG->wwwroot}/mod/dataform/import.php?d={$df->id()}",$errorfield);
            }

            foreach ($csvrecords as $csvrecord) {
                // add instance to dataform_records and respective 
                if ($recordadded = $df->process_entries('new', 0, true)) {
                    $recordid = $recordadded[0];
                    // Fill dataform_content with the values imported from the CSV file:
                    foreach ($csvrecord as $key => $value) {
                        $name = $fieldnames[$key];
                        $field = $fields[$name];
                        $content = new object();
                        $content->fieldid = $field->id;
                        $content->recordid = $recordid;
                        if ($field->type == 'richtextarea') {
                            // the only field type where HTML is possible
                            $value = clean_param($value, PARAM_CLEANHTML);
                        } else {
                            // remove potential HTML:
                            $patterns[] = '/</';
                            $replacements[] = '&lt;';
                            $patterns[] = '/>/';
                            $replacements[] = '&gt;';
                            $value = preg_replace($patterns, $replacements, $value);
                        }
                        $value = addslashes($value);
                        // for now, only for "latlong" and "url" fields, but that should better be looked up from
                        // $CFG->dirroot . '/mod/dataform/field/' . $field->type . '/field.class.php'
                        // once there is stored how many contents the field can have. 
                        if (preg_match("/^(latlong|url)$/", $field->type)) {
                            $values = explode(" ", $value, 2);
                            $content->content  = $values[0];
                            $content->content1 = $values[1];
                        } else {
                            $content->content = $value;
                        }
                        $oldcontent = get_record('dataform_content', 'fieldid', $field->id, 'recordid', $recordid);
                        $content->id = $oldcontent->id;
                        if (! update_record('dataform_content', $content)) {
                            print_error('cannotupdaterecord', '', '', $recordid);
                        }
                    }
                    $recordsadded++;
                    print get_string('added', 'moodle'). ' '. get_string('entry', 'dataform'). " (ID $recordid)<br />\n";
                }
            }
        }

        if ($recordsadded > 0) {
            notify($recordsadded. ' '. get_string('recordssaved', 'dataform'));
        } else {
            notify(get_string('recordsnotsaved', 'dataform'));
        }
        echo '<br />';
        echo '<div style="text-align:center">';
        echo '<a href="import.php?d='.$df->id().'">'.get_string('returntoimport', 'dataform').'</a>';
        echo '</div>';
    }
} else {
    // Import form
    echo '<div style="text-align:center">';
    echo '<form enctype="multipart/form-data" action="import.php" method="post">';
    $maxuploadsize = get_max_upload_file_size();
    echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'" />';
    echo '<input name="d" value="'.$df->id().'" type="hidden" />';
    echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />';

    // Upload from file
    print_simple_box_start('center','80%');
    print_heading(get_string('importentries', 'dataform'), '', 3);
    helpbutton('importcsv', get_string('csvimport', 'dataform'), 'dataform', true, false);

    echo '<table align="center" cellspacing="0" cellpadding="2" border="0">';

    // file
    echo '<tr>';
    echo '<td valign="top">'.get_string('csvfile', 'dataform').':</td>';
    echo '<td><input type="file" name="csvfile" size="30" />';
    echo '</td><tr>';

    // text
    echo '<tr><td valign="top" colspan="2">'.get_string('csvtext', 'dataform').':</td></tr>';
    echo '<tr><td valign="top" colspan="2">';
    print_textarea(false, 10, 72, 0, 0, 'csvtext', '');
    echo '</tr>';
    
    // delimiters
    echo '<td valign="top">'.get_string('fielddelimiter', 'dataform').':</td>';
    echo '<td valign="top"><input type="text" name="fielddelimiter" size="6" />';
    echo get_string('defaultfielddelimiter', 'dataform').'</td>';
    echo '</tr>';
    echo '<td valign="top">'.get_string('fieldenclosure', 'dataform').':</td>';
    echo '<td valign="top"><input type="text" name="fieldenclosure" size="6" />';
    echo get_string('defaultfieldenclosure', 'dataform').'</td>';
    echo '</tr>';
    echo '</table>';

    echo '<input type="submit" name="import" value="'.get_string('import', 'dataform').'" />';
    print_simple_box_end();


    echo '</form>';
    echo '</div>';
}

/// Finish the page
print_footer($df->course);


function my_file_get_contents($filename, $use_include_path = 0) {
/// Returns the file as one big long string

$data = "";
$file = @fopen($filename, "rb", $use_include_path);
if ($file) {
    while (!feof($file)) {
        $data .= fread($file, 1024);
    }
    fclose($file);
}
return $data;
}



// Read the records from the given file.
// Perform a simple field count check for each record.
function dataform_get_records_csv($fp, $fielddelimiter=',', $fieldenclosure="\n") {
global $db;

if (empty($fielddelimiter)) {
    $fielddelimiter = ',';
}
if (empty($fieldenclosure)) {
    $fieldenclosure = "\n";
}

$fieldnames = array();
$rows = array();

$fieldnames = fgetcsv($fp, 4096, $fielddelimiter, $fieldenclosure);

if (empty($fieldnames)) {
    return false;
}
$rows[] = $fieldnames;

while (($data = fgetcsv($fp, 4096, $fielddelimiter, $fieldenclosure)) !== false) {
    if (count($data) > count($fieldnames)) {
        // For any given record, we can't have more dataform entities than the number of fields.
        return false;
    }
    $rows[] = $data;
}

return $rows;
}

?>
