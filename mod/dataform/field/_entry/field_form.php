<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field__entry_form extends mod_dataform_field_form {

    function mod_dataform_field__entry_form($field) {
        parent::mod_dataform_field_form($field);
    }

    /**
     *
     */
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        // hidden optional params
        $mform->addElement('hidden', 'd', $this->_field->field->dataid);
        $mform->setType('d', PARAM_INT);

        $mform->addElement('hidden', 'type', $this->_field->type());
        $mform->setType('type', PARAM_ALPHA);

        $mform->addElement('hidden', 'fid', $this->_field->id());
        $mform->setType('fid', PARAM_INT);

        $mform->addElement('html', '<h2 class="mdl-align">'.get_string('entrysettings', 'dataform').'</h2>');

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', '');

        // if there is an admin limit select from dropdown
        if ($CFG->dataform_maxentries) { 
            $maxoptions = (array_combine(range(1, $CFG->dataform_maxentries),range(1, $CFG->dataform_maxentries)));

            // required entries
            $mform->addElement('select', 'entriesrequired', get_string('entriesrequired', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // required entries to view
            $mform->addElement('select', 'entriestoview', get_string('entriestoview', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // max entries
            $mform->addElement('select', 'maxentries', get_string('entriesmax', 'dataform'), $maxoptions);
            $mform->setDefault('maxentries', $CFG->dataform_maxentries);
        
        // no admin limit so enter any number
        } else { 
            // required entries
            $mform->addElement('text', 'entriesrequired', get_string('entriesrequired', 'dataform'));
            $mform->addRule('entriesrequired', null, 'numeric', null, 'client');
            // required entries to view
            $mform->addElement('text', 'entriestoview', get_string('entriestoview', 'dataform'));
            $mform->addRule('entriestoview', null, 'numeric', null, 'client');
            // max entries
            $mform->addElement('text', 'maxentries', get_string('entriesmax', 'dataform'));
            $mform->addRule('maxentries', null, 'numeric', null, 'client');
        }
        $mform->setHelpButton('entriesrequired', array('entriesrequired', get_string('entriesrequired', 'dataform'), 'dataform'));
        $mform->setHelpButton('entriestoview', array('entriestoview', get_string('entriestoview', 'dataform'), 'dataform'));
        $mform->setHelpButton('maxentries', array('entriesmax', get_string('entriesmax', 'dataform'), 'dataform'));

        // time limit to manage an entry
        $mform->addElement('text', 'timelimit', get_string('entrytimelimit', 'dataform'));
        $mform->setType('timelimit', PARAM_INT);
        $mform->setDefault('timelimit', '');
        $mform->addRule('timelimit', null, 'numeric', null, 'client');
        $mform->setHelpButton('timelimit', array("entrytimelimit", get_string('entrytimelimit', 'dataform'), 'dataform'));

        // approval
        $mform->addElement('selectyesno', 'approval', get_string('requireapproval', 'dataform'));
        $mform->setHelpButton('approval', array('requireapproval', get_string('requireapproval', 'dataform'), 'dataform'));

        // comments
        $mform->addElement('selectyesno', 'comments', get_string('comments', 'dataform'));
        $mform->setHelpButton('comments', array('comments', get_string('allowcomments', 'dataform'), 'dataform'));

        // entry rating
        $mform->addElement('modgrade', 'entryrating', get_string('entryrating', 'dataform'));
        $mform->setDefault('entryrating', 0);

        // entry locks
        $locksarray = array();
        $locksarray[] = &$mform->createElement('advcheckbox', 'lockonapproval', null, get_string('entrylockonapproval', 'dataform'), null, array(0,1));
        $locksarray[] = &$mform->createElement('advcheckbox', 'lockoncomments', null, get_string('entrylockoncomments', 'dataform'), null, array(0,2));
        $locksarray[] = &$mform->createElement('advcheckbox', 'lockonratings', null, get_string('entrylockonratings', 'dataform'), null, array(0,4));
        $mform->addGroup($locksarray, 'locksarr', get_string('entrylocks', 'dataform'), '<br />', false);
        $mform->setHelpButton('locksarr', array('locksarr', get_string('entrylocks', 'dataform'), 'dataform'));
        $mform->disabledIf('lockonapproval', 'approval', 'eq', 0);
        $mform->disabledIf('lockoncomments', 'comments', 'eq', 0);
        $mform->disabledIf('lockonratings', 'entryrating', 'eq', 0);

        $viewmenu = array(0 => 'Choose...') + $this->_field->df->get_views(null, true);

        // edit view
        $mform->addElement('select', 'singleedit', get_string('viewforedit', 'dataform'), $viewmenu);
        $mform->setHelpButton('singleedit', array('viewforedit', get_string('viewforedit', 'dataform'), 'dataform'));

        // single view
        $mform->addElement('select', 'singleview', get_string('viewformore', 'dataform'), $viewmenu);
        $mform->setHelpButton('singleview', array('viewformore', get_string('viewformore', 'dataform'), 'dataform'));

    // buttons
    //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    function validation($data) {
        return '';
    }
}
?>