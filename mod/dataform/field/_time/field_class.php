<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__time extends dataform_field_base {

    public $type = '_time';

    /**
     * 
     */
    public function dataform_field__time($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        $patterns = array('entryinfo' => array());

        // no edit mode for this field
        // if no record display nothing
        if (!$record) {  
            $patterns['entryinfo']['##'. $this->field->internalname. '##'] = '';
            $patterns['entryinfo']['##'. $this->field->internalname. ':day##'] = '';
            $patterns['entryinfo']['##'. $this->field->internalname. ':week##'] = '';
            $patterns['entryinfo']['##'. $this->field->internalname. ':month##'] = '';
            $patterns['entryinfo']['##'. $this->field->internalname. ':year##'] = '';
        } else {
            // convert commas before returning
            $patterns['entryinfo']['##'. $this->field->internalname. '##'] = str_replace(',', '&#44;', userdate($record->{$this->field->internalname}));
            $patterns['entryinfo']['##'. $this->field->internalname. ':day##'] = str_replace(',', '&#44;', userdate($record->{$this->field->internalname}, '%a'));
            $patterns['entryinfo']['##'. $this->field->internalname. ':week##'] = str_replace(',', '&#44;', userdate($record->{$this->field->internalname}, '%V'));
            $patterns['entryinfo']['##'. $this->field->internalname. ':month##'] = str_replace(',', '&#44;', userdate($record->{$this->field->internalname}, '%b'));
            $patterns['entryinfo']['##'. $this->field->internalname. ':year##'] = str_replace(',', '&#44;', userdate($record->{$this->field->internalname}, '%G'));
        }
        
        return $patterns;
    }

    /**
     * 
     */
    public function update_content($recordid, $value='', $name='') {
        return true;
    }

    /**
     * 
     */
    public function display_search($mform, $i = 0, $value = '') {
        if (is_array($value)){
            $from = $value[0];
            $to = $value[1];
        } else {
            $from = 0;
            $to = 0;
        }
    
        $elements = array();
        $elements[] = &$mform->createElement('date_time_selector', 'f_'. $i. '_'. $this->field->id. '_from', get_string('from'));
        $elements[] = &$mform->createElement('date_time_selector', 'f_'. $i. '_'. $this->field->id. '_to', get_string('to'));
        $mform->addGroup($elements, "searchelements$i", null, '<br />', false);
        $mform->setDefault('f_'. $i. '_'. $this->field->id. '_from', $from);
        $mform->setDefault('f_'. $i. '_'. $this->field->id. '_to', $to);
        foreach (array('year','month','day','hour','minute') as $fieldidentifier) {
            $mform->disabledIf('f_'. $i. '_'. $this->field->id. '_to['. $fieldidentifier. ']', "searchoperator$i", 'neq', 'BETWEEN');
        }
        $mform->disabledIf("searchelements$i", "searchoperator$i", 'eq', 'IN');
        $mform->disabledIf("searchelements$i", "searchoperator$i", 'eq', 'LIKE');
    }
    
    /**
     * 
     */
    public function parse_search($formdata, $i) {
        $time = array();

        if (!empty($formdata->{'f_'. $i. '_'. $this->field->id. '_from'})) {
            $time[0] = $formdata->{'f_'. $i. '_'. $this->field->id. '_from'};
        }
            
        if (!empty($formdata->{'f_'. $i. '_'. $this->field->id. '_to'})) {
            $time[1] = $formdata->{'f_'. $i. '_'. $this->field->id. '_to'};
        }

        if (!empty($time)) {
            return $time;   
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function get_search_sql($search) {
        list($not, $operator, $value) = $search;

        if (is_array($value)){
            $from = $value[0];
            $to = $value[1];
        } else {
            $from = 0;
            $to = 0;
        }
        
        if ($operator != 'BETWEEN') {
            if (!$operator) {
                $operator = '=';
            }
            return " $not r.{$this->field->internalname} $operator '$from' ";
        } else {
            return " $not (r.{$this->field->internalname} >= '$from' AND r.{$this->field->internalname} <= '$to') ";
        }
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return 'r.'. $this->field->internalname;
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
                $value = $data->{$this->field->internalname};
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
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        if (is_array($value)){
            $from = str_replace(',', '&#44;', userdate($value[0]));
            $to = str_replace(',', '&#44;', userdate($value[1]));
        } else {
            $from = str_replace(',', '&#44;', userdate(time()));
            $to = str_replace(',', '&#44;', userdate(time()));
        }
        if ($operator != 'BETWEEN') {
            return $not. ' '. $operator. ' '. $from;
        } else {
            return $not. ' '. $operator. ' '. $from. ' and '. $to;
        }
    }  

    /**
     *
     */
    public function export_text_value($entry) {
        return $entry->{$this->field->internalname};
    }

}
?>