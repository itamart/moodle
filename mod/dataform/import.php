<?php  // $Id$

/**
 *  Import:
 */
 
require_once('../../config.php');
require_once('mod_class.php');
require_once($CFG->libdir.'/uploadlib.php');
require_once('impexp_form.php');

$d = required_param('d', PARAM_INT); // dataform id

// Set a dataform object
$df = new dataform($d);

require_capability('mod/dataform:managetemplates', $df->context);

// build header to match the rest of the UI
$nav = build_navigation('', $df->cm);
print_header_simple($df->name(), '', $nav,
    '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
    navmenu($df->course, $df->cm), '', '');
print_heading(format_string($df->name()));

$currenttab = 'import';
include('tabs.php');

$mform = new mod_dataform_impexp_form('import.php?d='.$df->id(), $df, 'import');

// Process incoming upload request
if ($formdata = $mform->get_data()) {

    $importtype = $formdata->impexptype;
    if ($importtype == 'csv') {
        $entriesupdated = $entriesadded= 0;
        $csvdelimiter = $formdata->csvdelimiter ? $formdata->csvdelimiter : ',';
        $csvenclosure = $formdata->csvenclosure ? $formdata->csvenclosure : "\n";
        $csvrecords = null;
        
        if (!function_exists('str_getcsv')) {
            function str_getcsv($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) {
                $temp=fopen("php://memory", "rw");
                fwrite($temp, $input);
                fseek($temp, 0);
                $r = array();
                while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) {
                    $r[] = $data;
                }
                fclose($temp);
                return $r;
            }
        }    

        $text = '';
        //$fp = NULL;

        // get the csv records
        if (!empty($formdata->csvtext)) { // Upload from text
            $textlib = textlib_get_instance();
            $text = $formdata->csvtext;
            $text = preg_replace('!\r\n?!',"\n",$text);
            $text = $textlib->trim_utf8_bom($text); // remove Unicode BOM from first line
            if (!$csvrecords = str_getcsv($text, $csvdelimiter, $csvenclosure)) {
                print_error('csvfailed','dataform',"{$CFG->wwwroot}/mod/dataform/import.php?d={$df->id()}");
            }
            
        } else {  // no text so try from file
            $textlib = textlib_get_instance();
            $text = $mform->get_file_content('importfile');
            $text = preg_replace('!\r\n?!',"\n",$text);
            $text = $textlib->trim_utf8_bom($text); // remove Unicode BOM from first line
 
            if (!$csvrecords = str_getcsv($text, $csvdelimiter, $csvenclosure)) {
                print_error('csvfailed','dataform',"{$CFG->wwwroot}/mod/dataform/import.php?d={$df->id()}");
            }
        }
        
        // process the csv records
        if ($csvrecords) {
            //$db->debug = true;
            $csvfieldnames = array_shift($csvrecords);

            // check the fieldnames are valid
            $fieldsbyname = array();
            foreach ($df->get_fields() as $field) {
                $fieldsbyname[$field->name()] = $field;
            }
            $errorfield = '';
            foreach ($csvfieldnames as $name) {
                if (!isset($fieldsbyname[$name])) {
                    $errorfield .= "'$name' ";
                }
            }

            if (!empty($errorfield)) {
                print_error('fieldnotmatched','dataform',"{$CFG->wwwroot}/mod/dataform/import.php?d={$df->id()}",$errorfield);
            }

            // updating existing?
            if (isset($formdata->updateexisting)) {
                if (array_search('Entry', $csvfieldnames) !== false) {
                    // get all entries in the dataform
                    $entries = $df->get_entries()->entries;
                }
            }
            
            // process each csv record
            foreach ($csvrecords as $key => $csvrecord) {
                if ($csvrecord = array_combine($csvfieldnames, $csvrecord)) {
                    // first, update or add an entry
                    if (isset($entries) and $entryid = $csvrecord['Entry'] and isset($entries[$entryid])) { // update entry
                        $df->update_entry($entries[$entryid], $csvrecord); 
                    } else { // add a new entry
                        $entryid = $df->update_entry(0, $csvrecord);
                    }
                    
                    // then, add the entry's content
                    if ($entryid) { 
                        foreach ($csvrecord as $name => $value) {
                            $field = $fieldsbyname[$name];
                            if ($field->import_text_supported()) { // update content of only user fields
                                $value = addslashes($value);
                                // TODO what about multifield fields (e.g. date)?
                                if (!$field->update_content($entryid, $value)) {
                                    print_error('cannotupdaterecord', '', '', "$entryid, field $name in row: $key");
                                }
                            }
                        }
                        $entriesadded++;
                        print get_string('updated', 'moodle'). ' '. get_string('entry', 'dataform'). " (ID $entryid)<br />\n";
                    }
                } else {
                    print get_string('invalid', 'moodle'). ' '. get_string('record', 'moodle'). " $key<br />\n";
                }
            }
        }

        if (($entriesadded + $entriesupdated) > 0) {
            notify(($entriesadded + $entriesupdated). ' '. get_string('entriessaved', 'dataform'));
        } else {
            notify(get_string('entriesnotsaved', 'dataform'));
        }
        echo '<br />';
        echo '<div style="text-align:center">';
        echo '<a href="import.php?d='.$df->id().'">'.get_string('returntoimport', 'dataform').'</a>';
        echo '</div>';
        
        print_footer();
    } else if ($importtype == 'xml') {
/*
        // get the csv records
        if (!empty($formdata->uploadfile)) { // Upload from file
            $um = new upload_manager('uploadfile', false, false, null, false, 0);

            if ($um->preprocess_files()) {
                $filename = $um->files['uploadfile']['tmp_name'];

                // Large files are likely to take their time and memory. Let PHP know
                // that we'll take longer, and that the process should be recycled soon
                // to free up memory.
                @set_time_limit(0);
                @raise_memory_limit("96M");
                if (function_exists('apache_child_terminate')) {
                    @apache_child_terminate();
                }

    //} else if ($importtype == 'bck') {
*/       
    }
} else {
    // Import form
    $mform->display();
    print_footer();
}

/**
 *
 */
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

/**
 * Read the records from the given file.
 * Perform a simple field count check for each record.
 */
function dataform_get_records_csv1($fp, $fielddelimiter=',', $fieldenclosure="\n") {
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