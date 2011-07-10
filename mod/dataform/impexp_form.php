<?php  // $Id$

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden!');
}

require_once($CFG->libdir . '/formslib.php');

class mod_dataform_impexp_form extends moodleform {
    protected $_fields = array();

    // @param string $url: the url to post to
    // @param array $fields: objects in this dataform
    function mod_dataform_impexp_form($url, $df, $mode) {
        $this->_df = $df;
        $this->_mode = $mode;
        parent::moodleform($url);
    }

    function definition() {

        $mform =& $this->_form;

        // hidden optional params ???
        
    // impexp type
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'impexptypehdr', get_string('impexptype', 'dataform', ucfirst($this->_mode)));

        $typesarray = array();
        $typesarray[] = &$mform->createElement('radio', 'impexptype', null, get_string('impexpuserdatacsv', 'dataform'), 'csv');
        if ($this->_mode == 'export') {
            $typesarray[] = &$mform->createElement('radio', 'impexptype', null, get_string('impexpuserdataxls', 'dataform'), 'xls');
            $typesarray[] = &$mform->createElement('radio', 'impexptype', null, get_string('impexpuserdataods', 'dataform'), 'ods');
        }
        $typesarray[] = &$mform->createElement('radio', 'impexptype', null, get_string('impexpuserdataxml', 'dataform'), 'xml');
        $typesarray[] = &$mform->createElement('radio', 'impexptype', null, get_string('impexpactivitybackup', 'dataform'), 'bck');
        $mform->addGroup($typesarray, 'impexparr', '', '<br />', false);
        //$mform->addRule('impexparr', null, 'required');
        $mform->setDefault('impexptype', 'csv');

    // csv settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'csvsettingshdr', get_string('csvsettings', 'dataform'));

        $mform->addElement('text', 'csvdelimiter', get_string('csvdelimiter', 'dataform'), array('size'=>'10'));
        $mform->addElement('text', 'csvenclosure', get_string('csvenclosure', 'dataform'), array('size'=>'10'));
        // TODO: delimiter ; should not be allowed so add a rule
        $mform->setDefault('csvdelimiter', ',');
        $mform->setDefault('csvenclosure', '');
        $mform->disabledIf('csvdelimiter', 'impexptype', 'neq', 'csv');
        $mform->disabledIf('csvenclosure', 'impexptype', 'neq', 'csv');

    // entries filtering
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'impexpentrieshdr', get_string('impexpentries', 'dataform'));

        // search
        $mform->addElement('text', 'entriessearch', get_string('search', 'dataform'));
        $mform->setDefault('entriessearch', '');
        $mform->disabledIf('entriessearch', 'filter', 'neq', 0);
        
        // filter
        if (!$filtersmenu = $this->_df->filters_menu()) {
            $filtersmenu = array(0 => get_string('filtersnonedefined', 'dataform'));
        } else {
            $filtersmenu = array(0 => 'Choose...') + $filtersmenu;
        }
        $mform->addElement('select', 'filter', get_string('filter', 'dataform'), $filtersmenu);
        $mform->setHelpButton('filter', array('filter', get_string('filter', 'dataform'), 'dataform'));
        $mform->setDefault('filter', 0);

    // export fields selection
    //-------------------------------------------------------------------------------
        if ($this->_mode == 'export') {
            $mform->addElement('header', 'impexpfieldshdr', get_string('impexpfields', 'dataform'));

            if ($fields = $this->_df->get_fields()) {
                foreach($fields as $field) {
                    if($field->export_text_supported()) {
                        $mform->addElement('advcheckbox', 'field_'.$field->field->id, '<div title="' . s($field->field->description) . '">' . $field->name() . '</div>', ' (' . $field->type() . ')', array('group'=>1));
                        $mform->setDefault('field_'.$field->field->id, 1);
                        //$mform->disabledIf('field_'.$field->field->id, 'impexptype', 'eq', 'xml');
                        //$mform->disabledIf('field_'.$field->field->id, 'impexptype', 'eq', 'bck');
                    } else {
                        $mform->addElement('static', 'unsupported'.$field->field->id, $field->field->name, get_string('impexpunsupportedfield', 'dataform', $field->type()));
                    }
                }
                $this->add_checkbox_controller(1, null, null, 1);
            }
        }
        
    // import input
    //-------------------------------------------------------------------------------
        if ($this->_mode == 'import') {
            $mform->addElement('header', 'importinputhdr', get_string('impexpinput', 'dataform'));

            $mform->addElement('textarea', 'csvtext', get_string('impexpuploadtext', 'dataform'), 'wrap="virtual" rows="5" cols="60"');                        
            $mform->disabledIf('csvtext', 'importfile', 'neq', '');
            // upload file
            $mform->addElement('file', 'importfile', get_string('file'));
            $mform->disabledIf('importfile', 'csvtext', 'neq', '');
            // update existing entries
            $mform->addElement('checkbox', 'updateexisting', get_string('impexpupdateexisting', 'dataform'));
        }


//-------------------------------------------------------------------------------
        $this->add_action_buttons(false, get_string($this->_mode, 'dataform'));
    }

}

?>