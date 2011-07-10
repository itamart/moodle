<?php  // $Id$

/**
 *  Export
 */
 
require_once('../../config.php');
require_once('mod_class.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('impexp_form.php');

$d = required_param('d', PARAM_INT); // dataform id

// Set a dataform object
$df = new dataform($d);

require_capability('mod/dataform:managetemplates', $df->context);

// get fields for this dataform
if(!$fields = $df->get_fields()) {
    print_error('nofieldindataform', 'dataform');
}

$mform = new mod_dataform_impexp_form('export.php?d='.$df->id(), $df, 'export');

if ($formdata = $mform->get_data()) {

    // set the filter for retrieving entries
    if ($formdata->entriessearch) {
        $filter = $df->get_filter_from_id();
        $filter->search = $formdata->entriessearch;
    } else {
        $filter = $df->get_filter_from_id($formdata->filter);
    }

    // are there any entries to export?
    if ($entries = $df->get_entries($filter)->entries) {
    
        // process csv, xls and ods export data
        $exporttype = $formdata->impexptype;
        if (in_array($exporttype, array('csv', 'xls', 'ods'))) {
            $exportdata = array();

            // populate the header in first row of export
            foreach($fields as $key => $field) {
                if(empty($formdata->{'field_'.$field->id()})) {
                    // ignore values we aren't exporting
                    unset($fields[$key]);
                } else {
                    $exportdata[0][] = $field->name();
                }
            }

            $count = 1;
            foreach($entries as $entrie) {
                // get content indexed by fieldid
                $contents = get_records('dataform_contents', 'recordid', $entrie->id, 'fieldid', 'fieldid, content, content1, content2, content3, content4');
                foreach($fields as $fieldid => $field) {
                    $content = '';
                    if ($fieldid > 0) { // user fields
                        if(isset($contents[$field->id()])) {
                            $content = $field->export_text_value($contents[$field->id()]);
                        }
                    } else {
                        $content = $field->export_text_value($entrie);
                    }    
                    $exportdata[$count][] = $content;
                }
                $count++;
            }

            $filename = clean_filename($df->name(). '-'. gmdate("Ymd_Hi"). '-userdata-'. ($count-1). '-records'. '.'. $exporttype);
        }
        
        switch ($exporttype) {
            case 'csv':
                header("Content-Type: application/download\n");
                header("Content-Disposition: attachment; filename=$filename");
                header('Expires: 0');
                header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
                header('Pragma: public');

                $delimiter = $formdata->csvdelimiter;
                $encdelim = '&#' . ord($delimiter) . ';';
                foreach($exportdata as $row) {
                    foreach($row as $key => $column) {
                        $row[$key] = str_replace($delimiter, $encdelim, $column);
                    }
                    echo implode($delimiter, $row). "\r\n";
                }
                break;
                
            case 'xls':
                require_once("$CFG->libdir/excellib.class.php");
                $workbook = new MoodleExcelWorkbook('-');
                $workbook->send($filename);
                $worksheet = array();
                $worksheet[0] =& $workbook->add_worksheet('');
                $rowno = 0;

                foreach ($exportdata as $row) {
                    $colno = 0;
                    foreach($row as $col) {
                        $worksheet[0]->write($rowno, $colno, $col);
                        $colno++;
                    }
                    $rowno++;
                }
                $workbook->close();
                break;

            case 'ods':
                require_once("$CFG->libdir/odslib.class.php");
                $workbook = new MoodleODSWorkbook('-');
                $workbook->send($filename);
                $worksheet = array();
                $worksheet[0] =& $workbook->add_worksheet('');
                $rowno = 0;

                foreach ($exportdata as $row) {
                    $colno = 0;
                    foreach($row as $col) {
                        $worksheet[0]->write($rowno, $colno, $col);
                        $colno++;
                    }
                    $rowno++;
                }
                $workbook->close();
                break;

            case 'xml':
                $backupfile = $df->backup('userdata');
                $filename = basename($backupfile);

                header("Content-Type: application/download\n");
                header("Content-Disposition: attachment; filename=$filename");
                header('Expires: 0');
                header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
                header('Pragma: public');

                $handler = fopen($backupfile, 'rb');
                print fread($handler, filesize($backupfile));
                fclose($handler);
                
                @unlink($backupfile);
                break;
                
            case 'bck':
                $backupfile = $df->backup();
                $filename = basename($backupfile);

                header("Content-Type: application/download\n");
                header("Content-Disposition: attachment; filename=$filename");
                header('Expires: 0');
                header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
                header('Pragma: public');

                $handler = fopen($backupfile, 'rb');
                print fread($handler, filesize($backupfile));
                fclose($handler);
                
                @unlink($backupfile);
                break;
            
        }
        
        die;
    } else {
        $displaynotice = get_string('impexpnoentries', 'dataform', strtolower(get_string('export', 'dataform')));
    }
}

// build header to match the rest of the UI
$nav = build_navigation('', $df->cm);
print_header_simple($df->name(), '', $nav,
    '', '', true, update_module_button($df->cm->id, $df->course->id, get_string('modulename', 'dataform')),
    navmenu($df->course, $df->cm), '', '');
print_heading(format_string($df->name()));

$currenttab = 'export';
include('tabs.php');

if (!empty($displaynotice)) {
    notify($displaynotice);
}

$mform->display();
print_footer();

?>