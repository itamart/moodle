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

class dataform_field_menu extends dataform_field_base {

    public $type = 'menu';

    function dataform_field_menu($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    function display_edit($recordid = 0) {

        if ($recordid){
            $content = get_field('dataform_content', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
            $content = trim($content);
        } else {
            $content = '';
        }

        $str = '<div title="'.s($this->field->description).'">';

        $rawoptions = explode("\n",$this->field->param1);
        foreach ($rawoptions as $option) {
            $option = trim($option);
            if ($option) {
                $options[$option] = $option;
            }
        }

        $str .= choose_from_menu($options, 'field_'.$this->field->id. '_'. $recordid, $content,
                                 get_string('menuchoose', 'dataform'), '', '', true, false, 0, 'field_'.$this->field->id. '_'. $recordid);

        $str .= '</div>';

        return $str;
    }

    function display_search($content = '') {
        global $CFG;
/*
        $varcharcontent = sql_compare_text('content', 255);
        $sql = "SELECT DISTINCT $varcharcontent AS content
                  FROM {$CFG->prefix}dataform_content
                 WHERE fieldid={$this->field->id} AND content IS NOT NULL";

        $usedoptions = array();
        if ($used = get_records_sql($sql)) {
            foreach ($used as $data) {
                $value = $data->content;
                if ($value === '') {
                    continue;
                }
                $usedoptions[$value] = $value;
            }
        }

        $options = array();
        foreach (explode("\n",$this->field->param1) as $option) {
            $option = trim($option);
            if (!isset($usedoptions[$option])) {
                continue;
            }
            $options[$option] = $option;
        }
        if (!$options) {
            // oh, nothing to search for
            return '';
        }
*/
        // just show the menu options
        $options = explode("\n",$this->field->param1);

        return choose_from_menu($options, 'f_'.$this->field->id, stripslashes($content), 'choose', '', 0, true);
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
