<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field_calculated_form extends mod_dataform_field_form {

    function mod_dataform_field_calculated_form($field) {
        parent::mod_dataform_field_form($field);
    }

    /**
     *
     */
    function field_definition() {

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // TODO
        $fieldmenu = array(0 => get_string('choose')) + $this->_field->df->get_fields(range(-20,-1), true);

        // negation
        $mform->addElement('checkbox', 'param1', get_string('calculatednegation', 'dataform'));

        // operand1
        $mform->addElement('select', 'param2', get_string('calculatedoperand', 'dataform'), $fieldmenu);
        //$mform->setHelpButton('param2', array('viewforedit', get_string('calculatedoperand', 'dataform'), 'dataform'));
        
        // operator
        $operators = array(0 => get_string('choose'), '+' => '+', '-' => '-', '*' => '*', '/' => '/', '%' => '%');
        $mform->addElement('select', 'param3', get_string('calculatedoperator', 'dataform'), $operators);
        $mform->disabledIf('param3', 'param2', 'eq', 0);

        // operand2
        $mform->addElement('select', 'param4', get_string('calculatedoperand', 'dataform'), $fieldmenu);
        $mform->disabledIf('param4', 'param3', 'eq', 0);
        //$mform->setHelpButton('param4', array('calculatedoperand', get_string('calculatedoperand', 'dataform'), 'dataform'));
    }

}
?>