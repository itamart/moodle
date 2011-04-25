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

// param1 - field width
// param2 - field width units (px,em,%)
// param3 - field css class name
// param4 - autolinking

class dataform_field_text extends dataform_field_base {

    public $type = 'text';

    public function dataform_field_text($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    function parse_search() {
        return optional_param('f_'.$this->field->id, '', PARAM_NOTAGS);
    }

    function get_search_sql($value) {
        return " (c{$this->field->id}.fieldid = {$this->field->id} AND c{$this->field->id}.content LIKE '%{$value}%') ";
    }

    protected function display_edit($recordid = 0) {
        if ($recordid){
            $content = get_field('dataform_content', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
        } else {
            $content = '';
        }

        // beware get_field returns false for new, empty records MDL-18567
        if ($content === false) {
            $content = '';
        }

        if (!empty($this->field->param2)) {
            $width = ' style="width:'. s($this->field->param2). s($this->field->param3). ';" ';
        } else {
            $width = '';
        }
        
        if (!empty($this->field->param4)) {
            $class = ' class="'. s($this->field->param4). '" ';
        } else {
            $class = '';
        }
        
        $str = '<div title="'.s($this->field->description).'">';
        // param1
        $str .= '<input type="text" name="field_'. $this->field->id. '_'. $recordid. '" id="field_'. $this->field->id. '_'. $recordid. '" '. $class. $width. 'value="'.s($content).'" />';
        $str .= '</divn>';
        
        return $str;
    }

    public function display_search($value = '') {
        return '<input type="text" size="16" name="f_'.$this->field->id.'" value="'.$value.'" />';
    }

}

?>
