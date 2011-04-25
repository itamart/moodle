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

class dataform_field__time extends dataform_field_base {

    public $type = '_time';

    /**
     * 
     */
    public function dataform_field__time($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        $patterns = array('entryinfo' => array());

        // no edit mode for this field
        // if no record display nothing
        if (!$record) {  
            $patterns['entryinfo']['##'. $this->field->name. '##'] = '';
            $patterns['entryinfo']['##'. $this->field->name. ':day##'] = '';
            $patterns['entryinfo']['##'. $this->field->name. ':week##'] = '';
            $patterns['entryinfo']['##'. $this->field->name. ':month##'] = '';
            $patterns['entryinfo']['##'. $this->field->name. ':year##'] = '';
        } else {
            // convert commas before returning
            $patterns['entryinfo']['##'. $this->field->name. '##'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}));
            $patterns['entryinfo']['##'. $this->field->name. ':day##'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%a'));
            $patterns['entryinfo']['##'. $this->field->name. ':week##'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%V'));
            $patterns['entryinfo']['##'. $this->field->name. ':month##'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%b'));
            $patterns['entryinfo']['##'. $this->field->name. ':year##'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%G'));
        }
        
        return $patterns;
    }

    /**
     * 
     */
    public function display_search($value = 0) {
        $valuefrom = $valueto = 0;
        if ($value) {
            $value = explode('$', $value);
            $valuefrom = $value[0];
            $valueto = isset($value[1]) ? $value[1] : 0;
        }
        $str = 'From:&nbsp;'.
                print_date_selector('f_'. $this->field->id. '_d_from', 'f_'. $this->field->id. '_m_from', 'f_'. $this->field->id. '_y_from', $valuefrom, true).
                print_time_selector('f_'. $this->field->id. '_h_from', 'f_'. $this->field->id. '_n_from', $valuefrom, 1, true).
                '<br />To:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
                print_date_selector('f_'. $this->field->id. '_d_to', 'f_'. $this->field->id. '_m_to', 'f_'. $this->field->id. '_y_to', $valueto, true).
                print_time_selector('f_'. $this->field->id. '_h_to', 'f_'. $this->field->id. '_n_to', $valueto, 1, true);
        return $str;
    }
    
    /**
     * 
     */
    public function get_search_sql($value) {
        return " (r.{$this->field->name} >= '$valuefrom' AND r.{$this->field->name} <= '$valueto') "; 
    }

    /**
     * 
     */
    public function parse_search() {
        // time from
        $timefrom = 0;
        $minute   = optional_param('f_'.$this->field->id.'_n_from', 0, PARAM_INT);
        $hour = optional_param('f_'.$this->field->id.'_h_from', 0, PARAM_INT);
        $day   = optional_param('f_'.$this->field->id.'_d_from', 0, PARAM_INT);
        $month = optional_param('f_'.$this->field->id.'_m_from', 0, PARAM_INT);
        $year  = optional_param('f_'.$this->field->id.'_y_from', 0, PARAM_INT);
        if (!empty($minute) && !empty($hour) && !empty($day) && !empty($month) && !empty($year)) {
            $timefrom = make_timestamp($year, $month, $day, $hour, $minute, 0, 0, false);
        }
        $timeto = 0;
        $minute   = optional_param('f_'.$this->field->id.'_n_to', 0, PARAM_INT);
        $hour = optional_param('f_'.$this->field->id.'_h_to', 0, PARAM_INT);
        $day   = optional_param('f_'.$this->field->id.'_d_to', 0, PARAM_INT);
        $month = optional_param('f_'.$this->field->id.'_m_to', 0, PARAM_INT);
        $year  = optional_param('f_'.$this->field->id.'_y_to', 0, PARAM_INT);
        if (!empty($minute) && !empty($hour) && !empty($day) && !empty($month) && !empty($year)) {
            $timeto = make_timestamp($year, $month, $day, $hour, $minute, 0, 0, false);
        }
        return $timefrom. '$'. $timeto;
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