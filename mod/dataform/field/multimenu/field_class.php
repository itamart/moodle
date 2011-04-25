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

class dataform_field_multimenu extends dataform_field_base {

    public $type = 'multimenu';

    function dataform_field_multimenu($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }


    function display_edit($recordid = 0) {

        if ($recordid){
            $content = get_field('dataform_content', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
            $content = explode('##', $content);
        } else {
            $content = array();
        }

        $str = '<div title="'.s($this->field->description).'">';
        $str .= '<input name="field_'. $this->field->id. '_'. $recordid. '[xxx]" type="hidden" value="xxx"/>'; // hidden field - needed for empty selection
        $str .= '<select name="field_'. $this->field->id. '_'. $recordid. '[]" id="field_' . $this->field->id. '_'. $recordid. '" multiple="multiple">';

        foreach (explode("\n",$this->field->param1) as $option) {
            $option = trim($option);
            $str .= '<option value="' . s($option) . '"';

            if (in_array($option, $content)) {
                // Selected by user.
                $str .= ' selected = "selected"';
            }

            $str .= '>';
            $str .= $option . '</option>';
        }
        $str .= '</select>';
        $str .= '</div>';

        return $str;
    }

    function display_search($value = '') {
        global $CFG;

        if (is_array($value)){
            $content     = $value['selected'];
            $allrequired = $value['allrequired'] ? 'checked = "checked"' : '';
        } else {
            $content     = array();
            $allrequired = '';
        }

        static $c = 0;

        $str = '<select name="f_'.$this->field->id.'[]" multiple="multiple">';

        // display only used options
        $varcharcontent = sql_compare_text('content', 255);
        $sql = "SELECT DISTINCT $varcharcontent AS content
                  FROM {$CFG->prefix}dataform_content
                 WHERE fieldid={$this->field->id} AND content IS NOT NULL";

        $usedoptions = array();
        if ($used = get_records_sql($sql)) {
            foreach ($used as $data) {
                $valuestr = $data->content;
                if ($valuestr === '') {
                    continue;
                }
                $values = explode('##', $valuestr);
                foreach ($values as $value) {
                    $usedoptions[$value] = $value;
                }
            }
        }

        $found = false;
        foreach (explode("\n",$this->field->param1) as $option) {
            $option = trim($option);
            if (!isset($usedoptions[$option])) {
                continue;
            }
            $found = true;
            $str .= '<option value="' . s($option) . '"';

            if (in_array(addslashes($option), $content)) {
                // Selected by user.
                $str .= ' selected = "selected"';
            }
            $str .= '>' . $option . '</option>';
        }
        if (!$found) {
            // oh, nothing to search for
            return '';
        }

        $str .= '</select>';

        $str .= '&nbsp;<input name="f_'.$this->field->id.'_allreq" id="f_'.$this->field->id.'_allreq'.$c.'" type="checkbox" '.$allrequired.'/>';
        $str .= '<label for="f_'.$this->field->id.'_allreq'.$c.'">'.get_string('selectedrequired', 'dataform').'</label>';
        $c++;

        return $str;

    }

    function parse_search() {
        $selected    = optional_param('f_'.$this->field->id, array(), PARAM_NOTAGS);
        $allrequired = optional_param('f_'.$this->field->id.'_allreq', 0, PARAM_BOOL);
        if (empty($selected)) {
            // no searching
            return '';
        }
        return array('selected'=>$selected, 'allrequired'=>$allrequired);
    }

    function get_search_sql($value) {
        $allrequired = $value['allrequired'];
        $selected    = $value['selected'];
        $varcharcontent = sql_compare_text("c{$this->field->id}.content", 255);

        if ($selected) {
            $conditions = array();
            foreach ($selected as $sel) {
                $likesel = str_replace('%', '\%', $sel);
                $likeselsel = str_replace('_', '\_', $likesel);
                $conditions[] = "(c{$this->field->id}.fieldid = {$this->field->id} AND ($varcharcontent = '$sel'
                                                                               OR c{$this->field->id}.content LIKE '$likesel##%'
                                                                               OR c{$this->field->id}.content LIKE '%##$likesel'
                                                                               OR c{$this->field->id}.content LIKE '%##$likesel##%'))";
            }
            if ($allrequired) {
                return " (".implode(" AND ", $conditions).") ";
            } else {
                return " (".implode(" OR ", $conditions).") ";
            }
        } else {
            return " ";
        }
    }

    function update_content($recordid, $value, $name='') {
        $content = new object;
        $content->fieldid  = $this->field->id;
        $content->recordid = $recordid;
        $content->content  = $this->format_dataform_field_multimenu_content($value);

        if ($oldcontent = get_record('dataform_content','fieldid', $this->field->id, 'recordid', $recordid)) {
            $content->id = $oldcontent->id;
            return update_record('dataform_content', $content);
        } else {
            return insert_record('dataform_content', $content);
        }
    }

    function format_dataform_field_multimenu_content($content) {
        if (!is_array($content)) {
            return NULL;
        }
        $options = explode("\n", $this->field->param1);
        $options = array_map('trim', $options);

        $vals = array();
        foreach ($content as $key=>$val) {
            if ($key === 'xxx') {
                continue;
            }
            if (!in_array(stripslashes($val), $options)) {
                continue;
            }
            $vals[] = $val;
        }

        if (empty($vals)) {
            return NULL;
        }

        return implode('##', $vals);
    }


    function display_browse($recordid) {

        if ($content = get_record('dataform_content', 'fieldid', $this->field->id, 'recordid', $recordid)) {
            if (empty($content->content)) {
                return false;
            }

            $options = explode("\n",$this->field->param1);
            $options = array_map('trim', $options);

            $contentArr = explode('##', $content->content);
            $str = '';
            foreach ($contentArr as $line) {
                if (!in_array($line, $options)) {
                    // hmm, looks like somebody edited the field definition
                    continue;
                }
                $str .= $line . "<br />\n";
            }
            return $str;
        }
        return false;
    }
}
?>
