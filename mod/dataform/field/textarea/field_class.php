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

class dataform_field_textarea extends dataform_field_base {

    public $type = 'textarea';

    function dataform_field_textarea($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    function display_search($value = '') {
        return '<input type="text" size="16" name="f_'.$this->field->id.'" value="'.$value.'" />';
    }

    function parse_search() {
        return optional_param('f_'.$this->field->id, '', PARAM_NOTAGS);
    }

    function get_search_sql($value) {
        return " (c{$this->field->id}.fieldid = {$this->field->id} AND c{$this->field->id}.content LIKE '%{$value}%') ";
    }

    function display_edit($recordid = 0) {
        $text = '';

        if ($recordid){
            if ($content = get_record('dataform_content', 'fieldid', $this->field->id, 'recordid', $recordid)) {
                $text   = $content->content;
            }
        }

        $text = clean_text($text);

        $str = '<div title="'.$this->field->description.'">'; 
        $str .= '<textarea class="form-textarea" id="field_'. $this->field->id. '_'. $recordid. '" name="field_'. $this->field->id. '_'. $recordid. '" rows="'. $this->field->param3 .'" cols="'. $this->field->param2 .'">';
        $str .= s($text);
        $str .= '</textarea>';            
        $str .= '</div>';

        return $str;
    }
}
?>