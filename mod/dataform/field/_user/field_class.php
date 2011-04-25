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

class dataform_field__user extends dataform_field_base {

    public $type = '_user';

    /**
     * 
     */
    public function dataform_field__user($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function display_search($value = '') {
        // TODO: get list of course participants and display it
        //choose_from_menu($orderoptions, 'customsort_'. $fieldid, $sortorder, 'choose' , '', 0 , true)    
        $str = '<input type="text" size="16" name="f_'. $this->field->id. '" value="'. $value. '" />';
        return $str;
    }
    
    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        return NULL;
    }
            
    /**
     * 
     */
    public function get_search_sql($value = '') {
        if ($value) {
            return ' u.'. $this->field->name. ' '. sql_ilike(). "'%". $value. "%' ";
        } else {
            return '';
        }
    }

    /**
     * 
     */
    public function parse_search() {
        return optional_param('f_'.$this->field->id, '', PARAM_NOTAGS);
    }

    /**
     * 
     */
    public function update_content($recordid, $value, $name='') {
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return 'r.'. $this->field->name;
    }

}
?>