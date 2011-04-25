<?php // $Id$
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

//2/19/07:  Advanced search of the date field is currently disabled because it does not track
// pre 1970 dates and does not handle blank entrys.  Advanced search functionality for this field
// type can be enabled once these issues are addressed in the core API.

class dataform_field_date extends dataform_field_base {

    public $type = 'date';

    var $day   = 0;
    var $month = 0;
    var $year  = 0;

    function dataform_field_date($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    function display_edit($recordid = 0) {

        if ($recordid) {
            $content = (int) get_field('dataform_content', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
        } else {
            $content = time();
        }

        $str = '<div title="'.s($this->field->description).'">';
        $str .= print_date_selector('field_'. $this->field->id. '_day'.  '_'. $recordid, 'field_'.$this->field->id.'_month'.  '_'. $recordid,
                                    'field_'.$this->field->id.'_year'.  '_'. $recordid, $content, true);
        $str .= '</div>';

        return $str;
    }
    
    //Enable the following three functions once core API issues have been addressed.
    function display_search($value = 0) {
        return false;
        //return print_date_selector('f_'.$this->field->id.'_d', 'f_'.$this->field->id.'_m', 'f_'.$this->field->id.'_y', $value, true);
    }
    
    function get_search_sql($value) {
        return ' 1=1 ';
        //return " (c{$this->field->id}.fieldid = {$this->field->id} AND c{$this->field->id}.content = '$value') "; 
    }
    
    function parse_search() {
        return '';
       /* 
        $day   = optional_param('f_'.$this->field->id.'_d', 0, PARAM_INT);
        $month = optional_param('f_'.$this->field->id.'_m', 0, PARAM_INT);
        $year  = optional_param('f_'.$this->field->id.'_y', 0, PARAM_INT);
        if (!empty($day) && !empty($month) && !empty($year)) {
            return make_timestamp($year, $month, $day, 12, 0, 0, 0, false);
        }
        else {
            return 0;
        }
        */
    }

    function update_content($recordid, $value, $name='') {

        $names = explode('_',$name);
        $name = $names[2];          // day month or year

        $this->$name = $value;

        if ($this->day and $this->month and $this->year) {  // All of them have been collected now

            $content = new object;
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;
            $content->content = make_timestamp($this->year, $this->month, $this->day, 12, 0, 0, 0, false);

            if ($oldcontent = get_record('dataform_content','fieldid', $this->field->id, 'recordid', $recordid)) {
                $content->id = $oldcontent->id;
                return update_record('dataform_content', $content);
            } else {
                return insert_record('dataform_content', $content);
            }
        }
    }

    function display_browse($recordid) {

        global $CFG;

        if ($content = get_field('dataform_content', 'content', 'fieldid', $this->field->id, 'recordid', $recordid)){
            return userdate($content, get_string('strftimedate'), 0);
        }
    }

    function get_sort_sql($fieldname) {

         return sql_cast_char2int($fieldname, true);
    }


}

?>
