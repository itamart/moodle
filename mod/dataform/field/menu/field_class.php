<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_menu extends dataform_field_base {

    public $type = 'menu';

    /**
     * 
     */
    function dataform_field_menu($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    protected function display_edit($recordid = 0) {

        if ($recordid){
            $content = get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
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

    /**
     * 
     */
    public function display_search($mform, $i = 0, $value = '') {
        $optionsarr = explode("\n",$this->field->param1);
        $menuoptions = array();
        foreach ($optionsarr as $option) {
            $menuoptions[$option] = $option;
        }

        $select = &$mform->addElement('select', 'f_'. $i. '_'. $this->field->id, null, $menuoptions);
        $select->setSelected($value);
    }

    /**
     * 
     */
    function get_compare_text() {
        return sql_compare_text("c{$this->field->id}.content", 255);
    }

}
?>