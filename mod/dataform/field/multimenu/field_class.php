<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_multimenu extends dataform_field_base {

    public $type = 'multimenu';
    public $separators = array(
            array('name' => 'New line', 'chr' => '<br />'),
            array('name' => 'Space', 'chr' => '&#32;'),
            array('name' => ',', 'chr' => '&#44;'),
            array('name' => ', (with space)', 'chr' => '&#44;&#32;')
    );

    function dataform_field_multimenu($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }


    /**
     *
     */
    public function display_search($mform, $i = 0, $value = '') {
        
        if (is_array($value)){
            $content     = $value['selected'];
            $allrequired = $value['allrequired'] ? 'checked = "checked"' : '';
        } else {
            $content     = array();
            $allrequired = '';
        }

        $optionsarr = explode("\n",$this->field->param1);
        $menuoptions = array();
        foreach ($optionsarr as $option) {
            $menuoptions[$option] = $option;
        }
        
        $select = &$mform->addElement('select', 'f_'. $i. '_'. $this->field->id, null, $menuoptions);
        $select->setMultiple(true);

        foreach ($menuoptions as $option) {
            $option = trim($option);
            $slashedoption = addslashes($option);
            
            if (in_array($slashedoption, $content)) { // Selected by user.
                $select->setSelected($slashedoption);
            }
        }

        $mform->addElement('checkbox', 'f_'. $i. '_'. $this->field->id.'_allreq', null, ucfirst(get_string('requiredall', 'dataform')));
        $mform->setDefault('f_'. $i. '_'. $this->field->id.'_allreq', $allrequired);
    }

    /**
     *
     */
    function parse_search($formdata, $i) {
        $selected = optional_param('f_'. $i. '_'. $this->field->id, array(), PARAM_NOTAGS);
        if ($selected) {
            $allrequired = optional_param('f_'. $i. '_'. $this->field->id.'_allreq', 0, PARAM_BOOL);
            return array('selected'=>$selected, 'allrequired'=>$allrequired);
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
            $selected = implode(', ', $value['selected']);
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
        $content = new object;
        $content->fieldid  = $this->field->id;
        $content->recordid = $recordid;
        $content->content  = $this->format_content($value);

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
    protected function format_content($content) {
        // content from form
        if (is_array($content)) {
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
            } else {
                return implode('##', $vals);
            }
        
        // content from import
        } else {
            return $content;
        }
    }

    /**
     *
     */
    protected function display_edit($recordid = 0) {

        if ($recordid){
            $content = get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
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
}
?>