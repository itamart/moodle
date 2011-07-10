<?php
require_once $CFG->libdir.'/formslib.php';

class mod_dataform_filter_form extends moodleform {
    protected $_df = null;
    protected $_filter = null;

    function mod_dataform_filter_form($filter, $df) {
        $this->_df = $df;
        $this->_filter = $filter;
        
        parent::moodleform();
    }

    function definition() {

        $fields = $this->_df->get_fields();
        $fieldsoptions = array(0 => 'Choose...') + $this->_df->get_fields(array(-1), true);

        $mform =& $this->_form;
        
        // hidden optional params
        $mform->addElement('hidden', 'd', $this->_df->id());
        $mform->setType('d', PARAM_INT);
        
        $mform->addElement('hidden', 'fid', $this->_filter->id);
        $mform->setType('fid', PARAM_INT);
        
        $mform->addElement('hidden', 'update', 1);
        $mform->setType('update', PARAM_INT);

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name and description
        $mform->addElement('text', 'name', get_string('name'));
        $mform->addElement('text', 'description', get_string('description'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_NOTAGS);
            $mform->setType('description', PARAM_NOTAGS);
        }

         // visibility
        $visibilityoptions = array(0=>'hidden',1=>'visible');
        $mform->addElement('select', 'visible', get_string('visible'), $visibilityoptions);
        $mform->setHelpButton('visible', array('filtervisibility', get_string('filtervisibility', 'dataform'), 'dataform'));
        $mform->setDefault('visible', 1);

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'filterhdr', get_string('view:filter', 'dataform'));

        // entries per page
        $perpageoptions = array(0=>'Choose...',1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                            20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
        $mform->addElement('select', 'perpage', get_string('view:perpage', 'dataform'), $perpageoptions);
        $mform->setHelpButton('perpage', array('view:perpage', get_string('view:perpage', 'dataform'), 'dataform'));
        $mform->setDefault('perpage', 10);
                            
        // group by
        $mform->addElement('select', 'groupby', get_string('filtergroupby', 'dataform'), $fieldsoptions);
        $mform->setHelpButton('groupby', array('filtergroupby', get_string('filtergroupby', 'dataform'), 'dataform'));
        
        // search
        $mform->addElement('text', 'search', get_string('search'));
        
    // custom sort
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'customsorthdr', get_string('filtercustomsort', 'dataform'));

        $diroptions = array(0 => get_string('ascending', 'dataform'),
                            1 => get_string('descending', 'dataform'));

        $count = 0;

        // add current options
        if ($this->_filter->customsort) {
            $sortfields = unserialize($this->_filter->customsort);

            foreach ($sortfields as $fieldid => $sortdir) { 
                $optionsarr = array();
                $optionsarr[] = &$mform->createElement('select', 'sortfield'. $count, '', $fieldsoptions);
                $optionsarr[] = &$mform->createElement('select', 'sortdir'. $count, '', $diroptions);
                $mform->addGroup($optionsarr, 'sortoptionarr'. $count, null, ' ', false);
                $mform->setDefault('sortfield'. $count, $fieldid);
                $mform->setDefault('sortdir'. $count, $sortdir);
                $count++;
            }
        }

        // add 3 more options
        for ($prevcount = $count; $count < ($prevcount + 3); $count++) {
            $optionsarr = array();
            $optionsarr[] = &$mform->createElement('select', 'sortfield'. $count, '', $fieldsoptions);
            $optionsarr[] = &$mform->createElement('select', 'sortdir'. $count, '', $diroptions);
            $mform->addGroup($optionsarr, 'sortoptionarr'. $count, null, ' ', false);
            $mform->disabledIf('sortdir'. $count, 'sortfield'. $count, 'eq', 0);
            if ($count > $prevcount) {
                $mform->disabledIf('sortoptionarr'. $count, 'sortfield'. ($count-1), 'eq', 0);
            }
        }

    // custom search
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'customsearchhdr', get_string('filtercustomsearch', 'dataform'));

        $andoroptions = array(0 => ' and/or ', 'AND' => 'AND', 'OR' => 'OR');
        $operatoroptions = array(
                        '=' => 'Equal',
                        //'<>' => 'Not equal',
                        '>' => 'Greater than',
                        '<' => 'Less than',
                        '>=' => 'Greater than or equal',
                        '<=' => 'Less than or equal',
                        'BETWEEN' => 'BETWEEN',
                        'LIKE' => 'LIKE',
                        'IN' => 'IN'
        );
        
        $count = 0;

        $mform->addElement('html', '<table align="center">');

        // add current options
        if ($this->_filter->customsearch) {

            $searchfields = unserialize($this->_filter->customsearch);

            foreach ($searchfields as $fieldid => $searchfield) {
                foreach ($searchfield as $andor => $searchoptions) {
                    foreach ($searchoptions as $searchoption) {
                        if ($searchoption) {
                            list($not, $operator, $value) = $searchoption;
                        } else {
                            list($not, $operator, $value) = array('', '=', '');
                        }
                        
                        $mform->addElement('html', '<tr style="border-bottom:1px solid #dddddd;"><td valign="top" nowrap="nowrap">');

                        $optionsarr = array();
                        // and/or option
                        $optionsarr[] = &$mform->createElement('select', 'searchandor'. $count, '', $andoroptions);
                        // searach field
                        $optionsarr[] = &$mform->createElement('select', 'searchfield'. $count, '', $fieldsoptions);
                        $mform->addGroup($optionsarr, 'searchoption'. $count, null, ' ', false);
                        $mform->setDefault('searchandor'. $count, $andor);
                        $mform->setDefault('searchfield'. $count, $fieldid);

                        $mform->addElement('html', '</td><td valign="top" nowrap="nowrap">');
                        $operatorarr = array();
                        // not option
                        $operatorarr[] = &$mform->createElement('checkbox', 'searchnot'. $count, null, 'NOT');
                        // search operator
                        $operatorarr[] = &$mform->createElement('select', 'searchoperator'. $count, '', $operatoroptions);
                        $mform->addGroup($operatorarr, 'searchoperatorarr'. $count, null, ' ', false);
                        $mform->setDefault('searchnot'. $count, $not);
                        $mform->setDefault('searchoperator'. $count, $operator);

                        $mform->addElement('html', '</td><td valign="top" nowrap="nowrap" style="width:10px;">');
                        // field search elements
                        $fields[$fieldid]->display_search($mform, $count, $value);
                        $mform->addElement('html', '</td></tr>');

                        $count++;
                    }
                }
            }
        }

        // add 3 more options
        for ($prevcount = $count; $count < ($prevcount + 3); $count++) {
            $mform->addElement('html', '<tr style="border-bottom:1px solid #dddddd;"><td valign="top" nowrap="nowrap">');

            $optionsarr = array();
            $optionsarr[] = &$mform->createElement('select', 'searchandor'. $count, '', $andoroptions);
            $optionsarr[] = &$mform->createElement('select', 'searchfield'. $count, '', $fieldsoptions);
            $mform->addGroup($optionsarr, 'searchoption'. $count, null, ' ', false);
            $mform->disabledIf('searchfield'. $count, 'searchandor'. $count, 'eq', 0);
            if ($count > $prevcount) {
                $mform->disabledIf('searchoption'. $count, 'searchandor'. ($count-1), 'eq', 0);
            }
            $mform->addElement('html', '</td><td valign="top" nowrap="nowrap"></td></tr>');
        }
        
        $mform->addElement('html', '</table>');

    // buttons
    //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    function validation($data) {
        $errors= array();
        
        if ($this->_df->name_exists('filters', $this->_filter->id)) {
            $errors['invalidviewname'] = get_string('invalidviewname','dataform');
        }

        return $errors;
    }

}
?>