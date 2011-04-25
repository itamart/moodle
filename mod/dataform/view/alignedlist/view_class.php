<?php  // $Id$
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999-onwards Moodle Pty Ltd  http://moodle.com          //
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

require_once($CFG->dirroot. '/mod/dataform/view/entries/view_class.php');

/**
 * A template for displaying dataform entries in an aligned list
 * Parameters used:
 * param1 - reserved (filter)
 * param2 - reserved (max entries per page)
 * param3 - reserved (group by) 
 * param4 - list header section
 * param5 - repeated entry section 
 * param6 - unused
 * param7 - unused
 * param8 - unused 
 * param9 - unused 
 * param10 - unused 
 */
class dataform_view_alignedlist extends dataform_view_entries {
    protected $type = 'alignedlist';
    
    /**
     * Constructor
     */
    public function dataform_view_alignedlist($view = 0, $df = 0) {
        parent::dataform_view_entries($view, $df);
    }

    /**
     * 
     */
    public function get_form() {
        return new mod_dataform_view_alignedlist_form(array($this));
    }

    /**
     * 
     */
    public function generate_default_view() {
        // get all the fields for that database
        if (!$fields = $this->df->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }
        
        $header = array();
        $entries = array();
        
        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $header[] = $field->field->name;
                $entries[] = '[['. $field->field->name. ']]';
            }
        }
        $header[] = '';
        $header[] = '';
        $header[] = '';
        $header[] = '';
        $entries[] = '##more##';
        $entries[] = '##edit##';
        $entries[] = '##delete##';
        $entries[] = '##approve##';

        $this->view->param4 = implode(',', $header);
        $this->view->param5 = implode(',', $entries);

        // set views and filters menus and quick search
        $this->view->section = '<div class="mdl-align">
                                ##viewsmenu##&nbsp;&nbsp;##filtersmenu##
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                ##quicksearch##&nbsp;&nbsp;##quickperpage##&nbsp;&nbsp;##quickreset##
                                <br /><br />##addnewentry##
                                <br /><br />##pagingbar##
                                <div>';
    }

    /**
     * 
     */
    public function editors() {
        return array('section', 'param4', 'param5');
    }

    /**
     * 
     */
    protected function display_section($content, $name = '', $return = false) {
        $table->head = explode(',', $this->view->param4);
        //$table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center');
        //$table->wrap = array(false, false, false, false, false, false, false, false);
        foreach ($content as $entry) {   
            $table->data[] = $entry;
        }
        
        if (!$return) {
            echo '<div class="entriesview">';
            print_table($table);
            echo '</div>';
        } else {
            return '<div class="entriesview">'. print_table($table, true). '</div>';
        }
    }

    /**
     * 
     */
    protected function entry_text($patterns) {
        return explode(',', $this->replace_tags($patterns, $this->view->param5));
    }
    
    /**
     * 
     */
    protected function new_entry_text() {
        return explode(',', parent::new_entry_text());
    }

    /**
     * 
     */
    protected function replace_view_tags(){
        $patterns = $this->patterns();
        
        $this->view->section = $this->replace_tags($patterns, $this->view->section);
    }
    
}
?>