<?php // $Id$

//2/19/07:  Advanced search of the date field is currently disabled because it does not track
// pre 1970 dates and does not handle blank entrys.  Advanced search functionality for this field
// type can be enabled once these issues are addressed in the core API.

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_date extends dataform_field_base {

    public $type = 'date';

    protected $day   = 0;
    protected $month = 0;
    protected $year  = 0;

    function dataform_field_date($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        $patterns = array('fields' => array());
        $recordid = $record ? $record->id : 0;
        
        if ($edit) {
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_edit($recordid);
            //$patterns['fields']['[['. $this->field->name. ':day]]'] = '';
            //$patterns['fields']['[['. $this->field->name. ':week]]'] = '';
            //$patterns['fields']['[['. $this->field->name. ':month]]'] = '';
            //$patterns['fields']['[['. $this->field->name. ':year]]'] = '';
        } else { 
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_browse($recordid);
            //$patterns['fields']['[['. $this->field->name. ':day]]'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%a'));
            //$patterns['fields']['[['. $this->field->name. ':week]]'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%V'));
            //$patterns['fields']['[['. $this->field->name. ':month]]'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%b'));
            //$patterns['fields']['[['. $this->field->name. ':year]]'] = str_replace(',', '&#44;', userdate($record->{$this->field->name}, '%G'));
        }
        
        return $patterns;
    }

    /**
     * 
     */
    protected function display_edit($recordid = 0) {

        if ($recordid) {
            $content = (int) get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
        } else {
            $content = time();
        }

        $str = '<div title="'.s($this->field->description).'">';
        $str .= print_date_selector('field_'. $this->field->id.  '_'. $recordid. '_day', 'field_'.$this->field->id.  '_'. $recordid.'_month',
                                    'field_'.$this->field->id.  '_'. $recordid.'_year', $content, true);
        $str .= '</div>';

        return $str;
    }
    
    /**
     * 
     */
    protected function display_browse($recordid) {

        global $CFG;

        if ($content = get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid)){
            return userdate($content, get_string('strftimedate'), 0);
        }
    }

    /**
     * // TODO
     */
    public function update_content($recordid, $value='', $name='') {
        $updatenow = false;
        
        // update from form
        if ($name) {
            $names = explode('_',$name);
            if (!empty($names[3])) {          // day month or year
                $this->{$names[3]} = $value;
            }

            if ($this->day and $this->month and $this->year) {  // All of them have been collected now

                $content = new object;
                $content->fieldid = $this->field->id;
                $content->recordid = $recordid;
                $content->content = make_timestamp($this->year, $this->month, $this->day, 12, 0, 0, 0, false);
                $updatenow = true;
            }
        // update from import
        } else {
            $content = new object;
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;
            $content->content = $value;
            $updatenow = true;
        }
        
        if ($updatenow) {
            if ($oldcontent = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
                $content->id = $oldcontent->id;
                return update_record('dataform_contents', $content);
            } else {
                return insert_record('dataform_contents', $content);
            }
        }
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
            return " $not c{$this->field->id}.content $operator '$from' ";
        } else {
            return " $not c{$this->field->id}.content >= '$from' AND c{$this->field->id}.content <= '$to') ";
        }
    }

    /**
     * 
     */
    public function get_sort_sql($fieldname) {
         return sql_cast_char2int($fieldname, true);
    }

}

?>