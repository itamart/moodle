<?php  // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__approve extends dataform_field_base {

    public $type = '_approve';

    function dataform_field__approve($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function patterns($entry = 0, $edit = false, $enabled = false) {
        global $CFG;
        
        // default
        $patterns = array('actions' => array('##approve##' => ''));
        
        if ($this->df->data->approval) {
            if ((!$entry or $edit) and has_capability('mod/dataform:approve', $this->df->context)) {
                $patterns['actions']['##approve##'] = $this->display_edit($entry);
            } else {    // existing entry to browse 
                $patterns['actions']['##approve##'] = $this->display_browse($entry);
            }
        }
            
        return $patterns;
    }
            
    /**
     * 
     */
    public function display_search($mform, $i = 0, $value = '') {
        $options = array(0 => ucfirst(get_string('approvednot', 'dataform')), 1 => ucfirst(get_string('approved', 'dataform')));
        $select = &$mform->addElement('select', 'f_'. $i. '_'. $this->field->id, null, $options);
        $select->setSelected($value);
        // disable the 'not' and 'operator' fields
        $mform->disabledIf("searchnot$i", 'f_'. $i. '_'. $this->field->id, 'neq', 2);
        $mform->disabledIf("searchoperator$i", 'f_'. $i. '_'. $this->field->id, 'neq', 2);
    }

    /**
     * 
     */
    public function update_content($recordid, $value='', $name='') {
        $entrie = new object();
        $entrie->id = $recordid;
        $entrie->approved = $value;
        return update_record('dataform_entries', $entrie);
    }

    /**
     *
     */
    public function export_text_value($entry) {
        return $entry->approved;
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return 'r.approved';
    }

    /**
     * 
     */
    public function get_search_sql($search) {
        $value = $search[2];
        return " r.approved = $value "; 
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $CFG;
        $contentfull = $this->get_sort_sql();
        $sql = 'SELECT DISTINCT '. $contentfull.
                        ' FROM '. $CFG->prefix. 'dataform_entries r '. 
                        ' WHERE '. $contentfull. ' IS NOT NULL'.
                        ' ORDER BY '. $contentfull. ' '. ($sortdir ? 'DESC' : 'ASC');

        $distinctvalues = array();
        if ($options = get_records_sql($sql)) {
            foreach ($options as $data) {
                $value = $data->approved;
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
    }

    /**
     * 
     */
    protected function display_edit($entry) {

        if ($entry) {
            $entryid = $entry->id;
            $checked = $entry->approved ? ' checked ' : '';
        } else {
            $entryid = 0;
            $checked = '';
        }

        $str = '<div title="'.s($this->field->description).'">';

        $str .= '<input type="hidden" name="field_'. $this->field->id. '_'. $entryid. '[]" value="" />';
        $str .= '<input type="checkbox" id="field_'. $this->field->id. '_'. $entryid. '" name="field_' . $this->field->id. '_'. $entryid. '" '. $checked. 'value="1" />';

        $str .= '</div>';
        return $str;
    }

    /**
     * 
     */
    protected function display_browse($entry) {
        global $CFG;
        
        if ($entry and $entry->approved) {
            $approved = 'approved';
            $approval = 'disapprove';
            $approvedimagesrc = $CFG->pixpath. '/i/tick_green_big.gif';
        } else {
            $approved = 'disapproved';
            $approval = 'approve';
            $approvedimagesrc = $CFG->pixpath. '/i/cross_red_big.gif';
        }
        
        $approvedimage = '<img src="'. $approvedimagesrc. '" class="iconsmall" alt="'. get_string($approved, 'dataform'). '" title="'. get_string($approved, 'dataform'). '" />';

        if (has_capability('mod/dataform:approve', $this->df->context)) {
            return '<a href="'. $entry->baseurl. '&amp;'. $approval. '='. $entry->id. '&amp;sesskey='. sesskey(). '">'.
                    $approvedimage. '</a>';
        } else {
            return $approvedimage;
        }
    }

}
?>