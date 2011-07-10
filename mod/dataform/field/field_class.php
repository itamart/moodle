<?php  // $Id$

/**
 * Base class for Dataform Field Types
 * (see field/<field type>/field.class.php)
 */
class dataform_field_base {

    public $type = 'unknown';  // Subclasses must override the type with their name
    public $df = NULL;       // The dataform object that this field belongs to
    public $field = NULL;      // The field object itself, if we know it

    public $iconwidth = 16;    // Width of the icon for this fieldtype
    public $iconheight = 16;   // Width of the icon for this fieldtype

    public function dataform_field_base($field = 0, $df = 0) {   // Field or dataform or both, field can be id or object, dataform id or df
        if (empty($df)) {
            error('Programmer error: You must specify dataform id or object when defining a field class. ');
        } else if (is_object($df)) {
            $this->df = $df;
        } else {    // dataform id
            $this->df = new dataform($df);
        }

        if (!empty($field)) {
            if (is_object($field)) {
                $this->field = $field;  // Programmer knows what they are doing, we hope
            } else if (!$this->field = get_record('dataform_fields','id',$field)) {
                error('Bad field ID encountered: '.$field);
            }
        }
        if (empty($this->field)) {         // We need to define some default values
            $this->set_field();
        }
    }

    /**
     * Sets up a field object
     */
    public function set_field($forminput = NULL) {
        $this->field = new object;
        $this->field->id = isset($forminput) ? $forminput->fid : 0;
        $this->field->type   = $this->type;
        $this->field->dataid = $this->df->id();
        $this->field->name = isset($forminput->name) ? trim($forminput->name) : '';
        $this->field->description = isset($forminput->description) ? trim($forminput->description) : '';
        for ($i=1; $i<=10; $i++) {
            $this->field->{'param'.$i} = isset($forminput->{'param'.$i}) ? trim($forminput->{'param'.$i}) : '';
        }
        return true;
    }

    /**
     * Insert a new field in the database
     */
    public function insert_field() {
        if (empty($this->field)) {
            notify('Programmer error: Field has not been defined yet!  See define_field()');
            return false;
        }

        if (!$fieldid = insert_record('dataform_fields', $this->field)){
            notify('Insertion of new field failed!');
            return false;
        } else {
            return $fieldid;
        }
    }

    /**
     * Update a field in the database
     */
    public function update_field() {
        if (!update_record('dataform_fields', $this->field)) {
            notify('updating of field failed!');
            return false;
        }
        return true;
    }

    /**
     * Update the content of one dataform field in the dataform_contents table
     */
    public function update_content($recordid, $value='', $name='') {
        $content = new object();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = clean_param($value, PARAM_NOTAGS);

        if ($oldcontent = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
            $content->id = $oldcontent->id;
            return update_record('dataform_contents', $content);
        } else {
            return insert_record('dataform_contents', $content);
        }
    }

    /**
     * Delete a field completely
     */
    public function delete_field() {
        if (!empty($this->field->id)) {
            delete_records('dataform_fields', 'id', $this->field->id);
            $this->delete_content();
        }
        return true;
    }

    /**
     * Delete all content associated with the field
     */
    public function delete_content($recordid=0) {

        $this->delete_content_files($recordid);

        if ($recordid) {
            return delete_records('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid);
        } else {
            return delete_records('dataform_contents', 'fieldid', $this->field->id);
        }
    }

    /**
     * Deletes any files associated with this field
     */
    public function delete_content_files($recordid=0) {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');

        $dir = $CFG->dataroot.'/'.$this->df->course->id.'/'.$CFG->moddata.'/dataform/'.$this->df->id().'/'.$this->field->id;
        if ($recordid) {
            $dir .= '/'.$recordid;
        }

        return fulldelete($dir);
    }

    /**
     * Delete all content associated with the field
     */
    public function transfer_content($tofieldid) {
        global $CFG;

        if ($contents = get_records('dataform_contents', 'fieldid', $this->field->id)) {
            if (!$tofieldid) {
                return false;
            } else {
                foreach ($contents as $content) {
                    $content->fieldid = $tofieldid;
                    update_record('dataform_contents', $content);
                }

                // rename content dir if exists
                $path = $CFG->dataroot.'/'.$this->df->course->id.'/'.$CFG->moddata.'/dataform/'.$this->df->id();
                $olddir = "$path/". $this->field->id;
                $newdir = "$path/$tofieldid";
                file_exists($olddir) and rename($olddir, $newdir);
                return true;
            }
        }
        return false;
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $CFG;
        $content = sql_compare_text('c'. $this->field->id. '.'. $this->get_sort_field());
        $contentfull = $this->get_sort_sql($content);
        $sql = 'SELECT DISTINCT '. $contentfull.
                        ' FROM '. $CFG->prefix. 'dataform_contents c'. $this->field->id.
                        ' WHERE c'. $this->field->id. '.fieldid='. $this->field->id. ' AND '. $contentfull. ' IS NOT NULL'.
                        ' ORDER BY '. $contentfull. ' '. ($sortdir ? 'DESC' : 'ASC');

        $distinctvalues = array();
        if ($options = get_records_sql($sql)) {
            foreach ($options as $data) {
                $value = $data->content;
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
    }

    /**
     * Returns the field id
     */
    public function id() {
        return $this->field->id;
    }

    /**
     * Returns the field type
     */
    public function type() {
        return $this->field->type;
    }

    /**
     * Returns the name of the field
     */
    public function name() {
        return $this->field->name;
    }

    /**
     * Returns the type name of the field
     */
    public function typename() {
        return get_string('name'.$this->field->type, 'dataform');
    }

    /**
     * Prints the respective type icon
     */
    public function image() {
        global $CFG;

        $str = '<a href="fields.php?d='. $this->field->dataid. '&amp;fid='. $this->field->id.'&amp;mode=display&amp;sesskey='.sesskey().'">';
        $str .= '<img src="'.$CFG->modpixpath.'/dataform/field/'.$this->type.'/icon.gif" ';
        $str .= 'height="'.$this->iconheight.'" width="'.$this->iconwidth.'" alt="'.$this->type.'" title="'.$this->type.'" /></a>';
        return $str;
    }

    /**
     *
     */
    public function get_form() {
        global $CFG;

        require_once($CFG->dirroot. '/mod/dataform/field/'. $this->type. '/field_form.php');
        $formclass = 'mod_dataform_field_'. $this->type. '_form';
        return new $formclass($this);
    }

    /**
     * display the fields for editing or browsing
     * builtin fields must override
     */
    public function patterns($record = 0, $edit = false, $editable = false) {
        $patterns = array('fields' => array());
        $recordid = $record ? $record->id : 0;

        if ($edit) {
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_edit($recordid);
        } else {
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_browse($recordid);
        }

        return $patterns;
    }

    /**
     * Check if a field from an add form is empty
     */
    public function notemptyfield($value, $name) {
        return !empty($value);
    }

    /**
     * Returns the sortable field for the content. By default, it's just content
     * but for some plugins, it could be content 1 - content4
     */
    public function get_sort_field() {
        return 'content';
    }

    /**
     * Returns the SQL needed to refer to the column.  Some fields may need to CAST() etc.
     */
    public function get_sort_sql($fieldname) {
        return $fieldname;
    }

    /**
     *
     */
    public function get_search_sql($search) {
        list($not, $operator, $value) = $search;

        $ilike = sql_ilike();
        $searchvalue = "'$value'";

        switch ($operator) {
            case 'IN':
                $terms = preg_split("/\s*,\s*/", trim($value));
                $searchvalue = "('". implode("','", $terms). "')";
                break;

            case 'LIKE':
            case 'BETWEEN':
            case '':
                $operator = $ilike;
                $searchvalue = "'%$value%'";
                break;
            default:
                break;
        }

        // TODO check that field content name is not empty
        $fieldcontentname = $this->get_compare_text();

        if (!$not or in_array($operator, array($ilike, 'IN'))) {
            return " $fieldcontentname $not $operator $searchvalue ";
        } else {
            return " $not $fieldcontentname $operator $searchvalue ";
        }
    }

    /**
     *
     */
    public function get_compare_text() {
        return "c{$this->field->id}.content";
    }

    /**
     * Per default, it is assumed that the field supports text exporting. Override this (return false) on fields not supporting text exporting.
     */
    public function export_text_supported() {
        return true;
    }

    /**
     * Per default, it is assumed that the field supports text importing. Override this (return false) on fields not supporting text exporting.
     */
    public function import_text_supported() {
        return true;
    }

    /**
     * Per default, return the record's text value only from the "content" field.
     * Override this in user fields class if necesarry.
     * Override in builtin fields class.
     */
    public function export_text_value($content) {
        if ($this->export_text_supported()) {
            return $content->content;
        }
    }

    /**
     * Print the form element when adding or editing an entry
     */
    protected function display_edit($recordid = 0) {
    }

    /**
     * Print the content for browsing the entry
     */
    protected function display_browse($recordid) {
        if ($content = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
            if (isset($content->content)) {
                $options = new object();
                if ($this->field->param1 == '1') {  // We are autolinking this field, so disable linking within us
                    //$content->content = '<span class="nolink">'.$content->content.'</span>';
                    //$content->content1 = FORMAT_HTML;
                    $options->filter=false;
                }
                $options->para = false;
                $str = format_text($content->content, $content->content1, $options);
            } else {
                $str = '';
            }
            // replace commas for tabular views
            return str_replace(',', '&#44;', $str);
        }
        return false;
    }

    /**
     * Just in case a field needs to print something before the whole form
     */
    public function print_before_form() {
    }

    /**
     * Just in case a field needs to print something after the whole form
     */
    public function print_after_form() {
    }

    /**
     *
     */
    public function display_search($mform, $i = 0, $value = '') {
        $mform->addElement('text', 'f_'. $i. '_'. $this->field->id, null, array('size'=>'32'));
        $mform->setType('f_'. $i. '_'. $this->field->id, PARAM_NOTAGS);
        $mform->setDefault('f_'. $i. '_'. $this->field->id, $value);
    }

    /**
     *
     */
    public function parse_search($formdata, $i) {
        if (!empty($formdata->{'f_'. $i. '_'. $this->field->id})) {
            return $formdata->{'f_'. $i. '_'. $this->field->id};
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        return $not. ' '. $operator. ' '. $value;
    }
}
?>