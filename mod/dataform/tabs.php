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

// This file to be included so we can assume config.php has already been included.
// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab) or empty($df->data) or empty($df->course)) {
        error('You cannot call this script in that way');
    }

    $context = get_context_instance(CONTEXT_MODULE, $df->cm->id);

    $inactive = NULL;
    $activetwo = NULL;
    $tabs = array();
    $row = array();

    // Browse tab
    $row[] = new tabobject('browse', $CFG->wwwroot.'/mod/dataform/view.php?d='.$df->id(), get_string('browse','dataform'));

    // Management tab
    if (isloggedin()) {
        if (has_capability('mod/dataform:managetemplates', $context) or has_capability(DATAFORM_CAP_EXPORT, $context)) {
            $row[] = new tabobject('manage', $CFG->wwwroot.'/mod/dataform/fields.php?d='.$df->id(), get_string('manage','dataform'));
        }
    }

    $tabs[] = $row;

    if ($currenttab != 'browse') {
        $inactive = array();
        $inactive[] = 'manage';

        $row  = array();

        if (isloggedin()) {
            if (has_capability('mod/dataform:managetemplates', $context)) {
                $row[] = new tabobject('fields', $CFG->wwwroot.'/mod/dataform/fields.php?d='.$df->id(),
                             get_string('fields','dataform'));
                $row[] = new tabobject('views', $CFG->wwwroot.'/mod/dataform/views.php?d='.$df->id(),
                             get_string('views','dataform'));
                $row[] = new tabobject('filters', $CFG->wwwroot.'/mod/dataform/filters.php?d='.$df->id(),
                             get_string('filters','dataform'));
                $row[] = new tabobject('js', $CFG->wwwroot.'/mod/dataform/js.php?d='.$df->id().'&amp;edit=1',
                             get_string('jsinclude', 'dataform'));
                $row[] = new tabobject('css', $CFG->wwwroot.'/mod/dataform/css.php?d='.$df->id().'&amp;edit=1',
                             get_string('cssinclude', 'dataform'));
                $row[] = new tabobject('presets', $CFG->wwwroot.'/mod/dataform/preset.php?d='.$df->id(),
                             get_string('presets', 'dataform'));
                $row[] = new tabobject('import', $CFG->wwwroot.'/mod/dataform/import.php?d='.$df->id(),
                             get_string('import', 'dataform'));
            }
            if (has_capability(DATAFORM_CAP_EXPORT, $context)) {
                // The capability required to Export dataform records is centrally defined in 'lib.php'
                // and should be weaker than those required to edit Views, Fields and Presets.
                $row[] = new tabobject('export', $CFG->wwwroot.'/mod/dataform/export.php?d='.$df->id(),
                             get_string('export', 'dataform'));

                if ($currenttab == 'manage') {
                    $currenttab ='export';
                }
            }
        }

        $tabs[] = $row;
        $activetwo = array('manage');
    }
    // Print out the tabs and continue!
    print_tabs($tabs, $currenttab, $inactive, $activetwo);

?>
