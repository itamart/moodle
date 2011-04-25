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
 * A template for displaying dataform entries in as a simple vertical bar graph
 * Parameters used:
 * param1 - reserved (filter)
 * param2 - reserved (max entries per page)
 * param3 - reserved (group by) 
 * param4 - bars labels
 * param5 - repeated entry section  
 * param6 - unused
 * param7 - unused
 * param8 - unused 
 * param9 - unused 
 * param10 - unused 
 */
class dataform_view_bargraph extends dataform_view_entries {
    protected $type = 'bargraph';
    
    /**
     * Constructor
     */
    public function dataform_view_bargraph($view = 0, $df = 0) {
        parent::dataform_view_entries($view, $df);
    }

    /**
     * 
     */
    public function get_form() {
        return new mod_dataform_view_bargraph_form(array($this));
    }

    /**
     * 
     */
    protected function display_section($content, $name = '', $return = false) {
        $labels = array();
        $bars = array();
        
        foreach ($content as $labar) {
            $labels[] = $labar['label'];
            $bars[] = $labar['bar'];
        }

        $numcolumns = count($content);
        $table->head = $labels;
        $table->align = array_fill(0, $numcolumns, 'center');
        $table->size = array_fill(0, $numcolumns, '52px');
        $table->data[] = $bars;
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->width = '10px';
        $table->class = 'simplebargraph';                
        
        if (!$return) {
            print_box_start('generalbox entriesview');
            echo '<div class="entriesview">';
            print_table($table);
            echo '</div>';
            print_box_end();                
        } else {
            return print_box_start('generalbox entriesview', '', true). 
                    '<div class="entriesview">'. print_table($table, true). '</div>'.
                    print_box_end(true);                
        }
    }

    /**
     * 
     */
    public function generate_default_view() {
        // get all the fields for that database
        if (!$fields = $this->get_fields()) {
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
        return array('param4', 'param5', 'section');
    }

    /**
     * 
     */
    protected function entry_text($patterns) {
        $entrytext = '<div><br /><br /><br />'. $this->replace_tags($patterns, $this->view->param5). '<div style="background-color:#5555ff;border:1px solid #dddddd;width:50px;height:'. ($this->replace_tags($patterns, $this->view->param5)*30). 'px;"></div></div>';
        $entrylabel = $this->replace_tags($patterns, $this->view->param4);
        return array('bar' => $entrytext, 'label' => $entrylabel);
    }

    /**
     * 
     */
    protected function replace_view_tags(){
        $patterns = $this->patterns();
        
        $this->view->section = $this->replace_tags($patterns, $this->view->section);
    }

    /**
     * Just in case a view needs to print something before the whole form
     */
    protected function print_before_form() {
        echo '<style type="text/css">.simplebargraph td{vertical-align:bottom;border:0 none;}</style>';
        parent::print_before_form();
    }

    
}
?>