<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

// param1 - field width
// param2 - field width units (px,em,%)
// param3 - field css class name
// param4 - autolinking

class dataform_field_text extends dataform_field_base {

    public $type = 'text';

    public function dataform_field_text($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    protected function display_edit($recordid = 0) {
        if ($recordid){
            $content = get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
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

}

?>