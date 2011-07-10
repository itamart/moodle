<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_radiobutton extends dataform_field_base {

    public $type = 'radiobutton';
    public $separators = array(
            array('name' => 'New line', 'chr' => '<br />'),
            array('name' => 'Space', 'chr' => '&#32;'),
            array('name' => ',', 'chr' => '&#44;'),
            array('name' => ', (with space)', 'chr' => '&#44;&#32;')
    );

    function dataform_field_radiobutton($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     *
     */
    protected function display_edit($recordid = 0) {
        global $CFG;

        if ($recordid){
            $content = trim(get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid));
        } else {
            $content = '';
        }

        $str = '<div title="'.s($this->field->description).'">';
        //$str .= '<fieldset><legend><span class="accesshide">'.$this->field->name.'</span></legend>';

        $i = 0;
        $options = array();
        foreach (explode("\n",$this->field->param1) as $radio) {
            $radio = trim($radio);
            if ($radio === '') {
                continue; // skip empty lines
            }
            $optionstr = '<input type="radio" id="field_'. $this->field->id. '_'. $i. '_'. $recordid. '" name="field_'. $this->field->id. '_'. $recordid. '" ';
            $optionstr .= 'value="' . s($radio) . '" ';

            if ($content == $radio) {
                // Selected by user.
                $optionstr .= 'checked />';
            } else {
                $optionstr .= '/>';
            }

            $optionstr .= '<label for="field_'. $this->field->id. '_'. $i. '_'. $recordid. '">'.$radio.'</label>';
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
    public function display_search($mform, $i = 0, $value = '') {
        
        $elements = array();

        foreach (explode("\n",$this->field->param1) as $radio) {
            $radio = trim($radio);
            $elements[] = &$mform->createElement('radio', 'f_'. $i. '_'. $this->field->id, null, $radio, $radio);
        }
        
        $mform->addGroup($elements, 'searchelements'. $i, null, '<br />', false);
        if ($value) { // Selected by user.
            $mform->setDefault('f_'. $i. '_'. $this->field->id, $value);
        }
    }

    /**
     *
     */
    public function get_compare_text() {
        return sql_compare_text("c{$this->field->id}.content", 255);
    }

}
?>