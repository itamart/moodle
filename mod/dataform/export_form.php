<?php  // $Id$

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden!');
}
require_once($CFG->libdir . '/formslib.php');

class mod_dataform_export_form extends moodleform {
    var $_datafields = array();
     // @param string $url: the url to post to
     // @param array $datafields: objects in this dataform
    function mod_dataform_export_form($url, $datafields) {
        $this->_datafields = $datafields;
        parent::moodleform($url);
    }

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', 'notice', get_string('chooseexportformat', 'dataform'));
        $choices = csv_import_reader::get_delimiter_list();
        $key = array_search(';', $choices);
        if (! $key === FALSE) {
            // array $choices contains the semicolon -> drop it (because its encrypted form also contains a semicolon):
            unset($choices[$key]);
        }
        $typesarray = array();
        $typesarray[] = &MoodleQuickForm::createElement('radio', 'exporttype', null, get_string('csvwithselecteddelimiter', 'dataform') . '&nbsp;', 'csv');
        $typesarray[] = &MoodleQuickForm::createElement('select', 'delimiter_name', null, $choices);
        $typesarray[] = &MoodleQuickForm::createElement('radio', 'exporttype', null, get_string('excel', 'dataform'), 'xls');
        $typesarray[] = &MoodleQuickForm::createElement('radio', 'exporttype', null, get_string('ods', 'dataform'), 'ods');
        $mform->addGroup($typesarray, 'exportar', '', array(''), false);
        $mform->addRule('exportar', null, 'required');
        $mform->setDefault('exporttype', 'csv');
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }
        $mform->addElement('header', 'notice', get_string('chooseexportfields', 'dataform'));
        foreach($this->_datafields as $field) {
            if($field->text_export_supported()) {
                $mform->addElement('advcheckbox', 'field_'.$field->field->id, '<div title="' . s($field->field->description) . '">' . $field->field->name . '</div>', ' (' . $field->name() . ')', array('group'=>1));
                $mform->setDefault('field_'.$field->field->id, 1);
            } else {
                $a = new object;
                $a->fieldtype = $field->name();
                $mform->addElement('static', 'unsupported'.$field->field->id, $field->field->name, get_string('unsupportedexport', 'dataform', $a));
            }
        }
        $this->add_checkbox_controller(1, null, null, 1);
        $this->add_action_buttons(true, get_string('exportdataformrecords', 'dataform'));
    }

}

?>
