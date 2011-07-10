<?php // $Id$
// display download dialogue
require_once('../../config.php');
$file = optional_param('file', '', PARAM_PATH);
$type = optional_param('type', 'txt', PARAM_ALPHA);
$delete = optional_param('delete', 0, PARAM_INT);
$return = optional_param('return', '', PARAM_URL);
if ($file and confirm_sesskey()) {
    $filepath = $CFG->dataroot. '/'. $file;
    if (file_exists($filepath)) {
        $filename = basename($file);
        switch ($type) {
            case 'txt':
                header("Content-Type: application/download\n");
                header("Content-Disposition: attachment; filename=$filename");
                header('Expires: 0');
                header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
                header('Pragma: public');
                $handler = fopen($filepath, 'rb');
                print fread($handler, filesize($filepath));
                fclose($handler);
                break;

            case 'xls':
                require_once("$CFG->libdir/excellib.class.php");
                $workbook = new MoodleExcelWorkbook('-');
                $workbook->send($filename);
                $worksheet = array();
                $worksheet[0] =& $workbook->add_worksheet('');
                $rowno = 0;

                $handler = fopen($filepath, 'rb');
                $exportdata = unserialize(fread($handler, filesize($filepath)));
                fclose($handler);

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

                $handler = fopen($filepath, 'rb');
                $exportdata = unserialize(fread($handler, filesize($filepath)));
                fclose($handler);

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
        }
        if ($delete) {
            @unlink($filepath);
        }
    }
}
if ($return) {
    redirect($return);
}
die;
?>