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

class dataform_field_richtextarea extends dataform_field_base {

    public $type = 'richtextarea';

    function dataform_field_richtextarea($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    function display_edit($recordid = 0) {
        global $CFG;

        $text   = '';
        $format = 0;

        if ($recordid){
            if ($content = get_record('dataform_content', 'fieldid', $this->field->id, 'recordid', $recordid)) {
                $text   = $content->content;
                $format = $content->content1;
            }
        }

        $str = '<div title="'.$this->field->description.'">';

        if (can_use_richtext_editor()) {
            // Show a rich text html editor.
            $str .= $this->gen_textarea(true, $text);
            $str .= helpbutton("richtext", get_string("helprichtext"), 'moodle', true, true, '', true);
            $str .= '<input type="hidden" name="field_'. $this->field->id. '_content1'. '_'. $recordid. '" value="' . FORMAT_HTML . '" />';

        } else {
            // Show a normal textarea. Also let the user specify the format to be used.
            $str .= $this->gen_textarea(false, $text);

            // Get the available text formats for this field.
            $formatsForField = format_text_menu();
            $str .= '<br />';

            $str .= choose_from_menu($formatsForField, 'field_'. $this->field->id.
                                     '_content1'. '_'. $recordid, $format, 'choose', '', '', true);

            $str .= helpbutton('textformat', get_string('helpformatting'), 'moodle', true, false, '', true);
        }
        $str .= '</div>';
        return $str;
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

    function gen_textarea($usehtmleditor, $text='') {
        // MDL-16018: Don't print htmlarea with < 7 lines height, causes visualization problem
        $text = clean_text($text);
        $this->field->param3 = ($usehtmleditor && $this->field->param3 < 7 ? 7 : $this->field->param3);
        return print_textarea($usehtmleditor, $this->field->param3, $this->field->param2,
                              '', '', 'field_'.$this->field->id, $text, '', true, 'field_' . $this->field->id);
    }

    function print_after_form() {
        if (can_use_richtext_editor()) {
            use_html_editor('field_' . $this->field->id, '', 'field_' . $this->field->id);
        }
    }

    function update_content($recordid, $value, $name='') {
        $content = new object;
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;

        $names = explode('_', $name);
        if (!empty($names[2])) {
            $content->$names[2] = clean_param($value, PARAM_NOTAGS);  // content[1-4]
        } else {
            $content->content = clean_param($value, PARAM_CLEAN);
        }

        if ($oldcontent = get_record('dataform_content','fieldid', $this->field->id, 'recordid', $recordid)) {
            $content->id = $oldcontent->id;
            return update_record('dataform_content', $content);
        } else {
            return insert_record('dataform_content', $content);
        }
    }
}
?>