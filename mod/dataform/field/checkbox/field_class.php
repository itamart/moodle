<?php  // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_checkbox extends dataform_field_base {

    public $type = 'checkbox';
    public $separators = array(
            array('name' => 'New line', 'chr' => '<br />'),
            array('name' => 'Space', 'chr' => '&#32;'),
            array('name' => ',', 'chr' => '&#44;'),
            array('name' => ', (with space)', 'chr' => '&#44;&#32;')
    );

    function dataform_field_checkbox($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     *
     */
    public function display_search($mform, $i = 0, $value = '') {
        if (is_array($value)){
            $content     = $value['checked'];
            $allrequired = $value['allrequired'] ? 'checked = "checked"' : '';
        } else {
            $content     = array();
            $allrequired = '';
        }

        $elements = array();
        
        foreach (explode("\n",$this->field->param1) as $checkbox) {
            $checkbox = trim($checkbox);
            $slashedcheckbox = addslashes($checkbox);
            
            $elements[] = &$mform->createElement('checkbox', 'f_'. $i. '_'. $this->field->id.'_'. $slashedcheckbox, null, $checkbox);
            $mform->setType('f_'. $i. '_'. $this->field->id.'_'. $slashedcheckbox, PARAM_NOTAGS);
            if (in_array($slashedcheckbox, $content)) { // Selected by user.
                $mform->setDefault('f_'. $i. '_'. $this->field->id.'_'. $slashedcheckbox, 'checked');
            }
        }

        $elements[] = &$mform->createElement('checkbox', 'f_'. $i. '_'. $this->field->id.'_allreq', null, ucfirst(get_string('requiredall', 'dataform')));
        $mform->setDefault('f_'. $i. '_'. $this->field->id.'_allreq', $allrequired);

        $mform->addGroup($elements, 'searchelements'. $i, null, '<br />', false);
    }
    
    /**
     *
     */
    public function parse_search($formdata, $i) {
        $selected = array();
        
        foreach (explode("\n",$this->field->param1) as $checkbox) {
            $checkbox = trim($checkbox);
            $slashedcheckbox = addslashes($checkbox);
            if (!empty($formdata->{'f_'. $i. '_'. $this->field->id.'_'. $slashedcheckbox})) {
                $selected[] = $checkbox;
            }
        }
        if ($selected) {
            if (!empty($formdata->{'f_'. $i. '_'. $this->field->id.'_allreq'})) {
                $allrequired = $formdata->{'f_'. $i. '_'. $this->field->id.'_allreq'};
            } else {
                $allrequired = '';
            }
            return array('checked'=>$selected, 'allrequired'=>$allrequired);
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        if (is_array($value)){
            $selected = implode(', ', $value['checked']);
            $allrequired = '('. ($value['allrequired'] ? get_string('requiredall') : get_string('requirednotall')). ')';
            return $not. ' '. $operator. ' '. $selected. ' '. $allrequired;
        } else {
            return false;
        }
    }  

    /**
     *
     */
    public function get_search_sql($search) {
        list($not, , $value) = $search;

        $allrequired = $value['allrequired'];
        $selected    = $value['checked'];
        $varcharcontent = sql_compare_text("c{$this->field->id}.content", 255);

        if ($selected) {
            $conditions = array();
            foreach ($selected as $sel) {
                $likesel = str_replace('%', '\%', $sel);
                $likeselsel = str_replace('_', '\_', $likesel);
                $conditions[] = "($varcharcontent = '$sel'
                                   OR c{$this->field->id}.content LIKE '$likesel##%'
                                   OR c{$this->field->id}.content LIKE '%##$likesel'
                                   OR c{$this->field->id}.content LIKE '%##$likesel##%')";
            }
            if ($allrequired) {
                return " $not (".implode(" AND ", $conditions).") ";
            } else {
                return " $not (".implode(" OR ", $conditions).") ";
            }
        } else {
            return " ";
        }
    }

    /**
     *
     */
    public function update_content($recordid, $value='', $name='') {
        $content = new object();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = $this->format_content($value);

        if ($oldcontent = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
            $content->id = $oldcontent->id;
            return update_record('dataform_contents', $content);
        } else {
            return insert_record('dataform_contents', $content);
        }
    }

    /**
     *
     */
    protected function display_edit($recordid = 0) {
        global $CFG;

        $content = array();

        if ($recordid) {
            $content = get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
            $content = explode('##', $content);
        } else {
            $content = array();
        }

        $str = '<div title="'.s($this->field->description).'">';

        $i = 0;
        $options = array();
        foreach (explode("\n", $this->field->param1) as $checkbox) {
            $checkbox = trim($checkbox);
            if ($checkbox === '') {
                continue; // skip empty lines
            }
            $optionstr = '<input type="hidden" name="field_'. $this->field->id. '_'. $recordid. '[]" value="" />';
            $optionstr .= '<input type="checkbox" id="field_'. $this->field->id. '_'. $i. '_'. $recordid. '" name="field_' . $this->field->id. '_'. $recordid. '[]" ';
            $optionstr .= 'value="' . s($checkbox) . '" ';

            if (array_search($checkbox, $content) !== false) {
                $optionstr .= 'checked />';
            } else {
                $optionstr .= '/>';
            }
            $optionstr .= '<label for="field_'. $this->field->id. '_'. $i. '_'. $recordid.'">'.$checkbox.'</label>';
            $options[] = $optionstr;
            $i++;
        }
        if ($options) {
            $str .= implode($this->separators[$this->field->param2]['chr'], $options);
        }
        $str .= '</div>';
        return $str;
    }

    /**
     *
     */
    protected function display_browse($recordid) {

       if ($content = get_record('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid)) {
            if (empty($content->content)) {
                return false;
            }

            $options = explode("\n",$this->field->param1);
            $options = array_map('trim', $options);

            $contentArr = explode('##', $content->content);
            $str = array();
            foreach ($contentArr as $line) {
                if (!in_array($line, $options)) {
                    // hmm, looks like somebody edited the field definition
                    continue;
                }
                $str[] = $line;
            }
            return implode($this->separators[$this->field->param2]['chr'], $str);
        }
        return false;
    }

    /**
     *
     */
    protected function format_content($content) {
        // content from form
        if (is_array($content)) {
            $options = explode("\n", $this->field->param1);
            $options = array_map('trim', $options);

            $vals = array();
            foreach ($content as $key => $val) {
                if ($key === 'xxx') {
                    continue;
                }
                if (!in_array(stripslashes($val), $options)) {
                    continue;
                }
                $vals[] = $val;
            }

            if (empty($vals)) {
                return null;
            } else {
                return implode('##', $vals);
            }
        
        // content from import
        } else {
            return $content;
        }
    }

}
?>