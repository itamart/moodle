<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_textarea extends dataform_field_base {

    public $type = 'textarea';
    
    protected $text = '';
    protected $format = FORMAT_HTML;

    function dataform_field_textarea($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function is_editor() {
        return $this->field->param1;
    }
    
    /**
     * display the fields for editing or browsing
     * builtin fields must override
     */
    public function patterns($record = 0, $edit = false) {
        $patterns = array('fields' => array());
        $recordid = $record ? $record->id : 0;
        $fieldid = $this->field->id;
        
        if ($edit) {
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_edit($recordid);
            if ($recordid) {
                $patterns['fields']['[['. $this->field->name. ':inpopup]]'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;edit=field", '', 'Click to edit', 500, 700, null, null, true));
                $patterns['fields']['[['. $this->field->name. ':editinpopup]]'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;edit=field", '', 'Click to edit', 500, 700, null, null, true));
            } else {
                // new entry so don't send to popup but open in view
                $patterns['fields']['[['. $this->field->name. ':inpopup]]'] = $this->display_edit($recordid);
                $patterns['fields']['[['. $this->field->name. ':editinpopup]]'] = $this->display_edit($recordid);
            }            
        } else { 
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_browse($recordid);
            $patterns['fields']['[['. $this->field->name. ':editinpopup]]'] = $this->display_browse($recordid);
            $patterns['fields']['[['. $this->field->name. ':inpopup]]'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=field", '', 'Click to view', 500, 700, null, null, true));
        }
        
        return $patterns;
    }

    /**
     * 
     */
    public function display_popup($record = 0, $params = null) {
        $recordid = $record ? $record->id : 0;
        
        if (isset($params['show'])) {
            return $this->display_browse($recordid);
        } else if (isset($params['edit'])) {
            return $this->display_edit($recordid);
        } else {
            return '';
        }
    }

    /**
     * 
     */
    protected function display_edit($recordid = 0) {
        global $CFG;

        $text   = '';
        $format = 0;

        if ($recordid){
            if ($content = get_record('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid)) {
                $text   = $content->content;
                $format = $content->content1;
            }
        }

        $str = '<div title="'.$this->field->description.'">';

        if ($this->is_editor() and can_use_richtext_editor()) {
            // Show a rich text html editor.
            $str .= $this->gen_textarea(true, $recordid, $text);
            $str .= helpbutton("richtext", get_string("helprichtext"), 'moodle', true, true, '', true);
            $str .= '<input type="hidden" name="field_'. $this->field->id. '_'. $recordid. '_content1'. '" value="' . FORMAT_HTML . '" />';

        } else {
            // Show a normal textarea. Also let the user specify the format to be used.
            $str .= $this->gen_textarea(false, $recordid, $text);

            // Get the available text formats for this field.
            $formatsForField = format_text_menu();
            $str .= '<br />';

            $str .= choose_from_menu($formatsForField, 'field_'. $this->field->id. '_'. $recordid.
                                     '_content1', $format, 'choose', '', '', true);

            $str .= helpbutton('textformat', get_string('helpformatting'), 'moodle', true, false, '', true);
        }
        $str .= '</div>';
        return str_replace(',', '&#44;', $str);
    }

    /**
     * 
     */
    protected function gen_textarea($usehtmleditor, $recordid, $text='') {
        // MDL-16018: Don't print htmlarea with < 7 lines height, causes visualization problem
        $text = clean_text($text);
        $this->field->param3 = ($usehtmleditor && $this->field->param3 < 7 ? 7 : $this->field->param3);
        return print_textarea($usehtmleditor, $this->field->param3, $this->field->param2,
                              '', '', 'field_'.$this->field->id. '_'. $recordid, $text, '', true, 'field_' . $this->field->id. '_'. $recordid);
    }

    /**
     * 
     */
    public function use_html_editor($entryid) {
        if ($this->field->param1 and can_use_richtext_editor()) {
            use_html_editor('field_' . $this->field->id. '_'. $entryid, '', 'field_' . $this->field->id. '_'. $entryid);
        }
    }

    /**
     * 
     */
    public function update_content($recordid, $value='', $name='') {
        $updatenow = false;
        
        // update from form
        if ($name) {
            $names = explode('_',$name);
            if (!empty($names[3])) {          // format
                $this->format = $value;
            } else {
                $this->text = $value;
            }
            
            // TODO this may update twice
            if ($this->text and $this->format) {  // All of them have been collected now
                $content = new object;
                $content->fieldid = $this->field->id;
                $content->recordid = $recordid;
                
                $options = new object();
                $options->noclean = true;

                $content->content = format_text($this->text, $this->format, $options);
                $content->content1 = $this->format;
                $updatenow = true;
            }
                        
        // update from csv
        } else {
            $content = new object;
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;
            
            $options = new object();
            $options->noclean = true;
            
            if (strpos($value, '##') !== false) {   // a format is defined so get it first
                $value = explode('##', $value);
                if ($value[0]) { // that is, if there is text
                    // get the format
                    $content->content1 = clean_param($value[1], PARAM_INT);
                    // get the formatted text
                    $content->content = format_text($value[0], $content->content1, $options);
                    $updatenow = true;
                }
            } else {    // no specific format so use the default
                $content->content = format_text($value, $this->format, $options);
                $updatenow = true;
            }
        }

        if ($updatenow) {
            if ($oldcontent = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
                $content->id = $oldcontent->id;
                return update_record('dataform_contents', $content);
            } else {
                return insert_record('dataform_contents', $content);
            }
        }
    }

    /**
     *
     */
    public function export_text_value($content) {
        $exporttext = $content->content;
        if ($content->content1 != FORMAT_HTML) {
            $exporttext .= "##$content->content1";
        }
        return $exporttext;
    }

}
?>