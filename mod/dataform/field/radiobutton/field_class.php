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

class dataform_field_radiobutton extends dataform_field_base {

    public $type = 'radiobutton';

    function dataform_field_radiobutton($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }


    function display_edit($recordid = 0) {
        global $CFG;

        if ($recordid){
            $content = trim(get_field('dataform_content', 'content', 'fieldid', $this->field->id, 'recordid', $recordid));
        } else {
            $content = '';
        }

        $str = '<div title="'.s($this->field->description).'">';
        $str .= '<fieldset><legend><span class="accesshide">'.$this->field->name.'</span></legend>';

        $i = 0;
        foreach (explode("\n",$this->field->param1) as $radio) {
            $radio = trim($radio);
            if ($radio === '') {
                continue; // skip empty lines
            }
            $str .= '<input type="radio" id="field_'. $this->field->id. '_'. $i. '_'. $recordid. '" name="field_'. $this->field->id. '_'. $recordid. '" ';
            $str .= 'value="' . s($radio) . '" ';

            if ($content == $radio) {
                // Selected by user.
                $str .= 'checked />';
            } else {
                $str .= '/>';
            }

            $str .= '<label for="field_'. $this->field->id. '_'. $i. '_'. $recordid. '">'.$radio.'</label><br />';
            $i++;
        }
        $str .= '</fieldset>';
        $str .= '</div>';
        return $str;
    }

     function display_search($value = '') {
        global $CFG;

        $varcharcontent = sql_compare_text('content', 255);
        $used = get_records_sql(
            "SELECT DISTINCT $varcharcontent AS content
               FROM {$CFG->prefix}dataform_content
              WHERE fieldid={$this->field->id}
             ORDER BY $varcharcontent");

        $options = array();
        if(!empty($used)) {
            foreach ($used as $rec) {
                $options[$rec->content] = $rec->content;  //Build following indicies from the sql.
            }
        }
        return choose_from_menu($options, 'f_'.$this->field->id, $value, 'choose', '', 0, true);
    }

    function parse_search() {
        return optional_param('f_'.$this->field->id, '', PARAM_NOTAGS);
    }

    function get_search_sql($value) {
        $varcharcontent = sql_compare_text("c{$this->field->id}.content", 255);
        return " (c{$this->field->id}.fieldid = {$this->field->id} AND $varcharcontent = '$value') ";
    }

}
?>
