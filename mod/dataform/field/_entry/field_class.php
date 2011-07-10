<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__entry extends dataform_field_base {

    public $type = '_entry';
    
    /**
     * 
     */
    public function dataform_field__entry($field = 0, $df = 0) {
        global $CFG;
        
        parent::dataform_field_base($field, $df);

        // df data settings
        $this->field->entriesrequired = $this->df->data->entriesrequired;
        $this->field->entriestoview = $this->df->data->entriestoview;
        if (!$this->df->data->maxentries or $this->df->data->maxentries > $CFG->dataform_maxentries) {
            $this->field->maxentries = $CFG->dataform_maxentries;
        } else {
            $this->field->maxentries = $this->df->data->maxentries;
        }
        $this->field->timelimit = $this->df->data->timelimit;
        $this->field->approval = $this->df->data->approval;
        $this->field->comments = $this->df->data->comments;
        $this->field->entryrating = $this->df->data->entryrating;
        $this->field->lockonapproval = $this->df->data->locks & $this->df->locks('approval');
        $this->field->lockoncomments = $this->df->data->locks & $this->df->locks('comments');
        $this->field->lockonratings = $this->df->data->locks & $this->df->locks('ratings');
        $this->field->singleedit = $this->df->data->singleedit;
        $this->field->singleview = $this->df->data->singleview;
        
        // set_field shouldn't be called in the constructor for builtin fields
    }

    /**
     * Sets up a field object
     */
    public function set_field($forminput = null) {
        // df entry settings
        if (isset($forminput->entriesrequired)) $this->field->entriesrequired = $forminput->entriesrequired;
        if (isset($forminput->entriestoview)) $this->field->entriestoview = $forminput->entriestoview;
        if (isset($forminput->maxentries)) $this->field->maxentries = $forminput->maxentries;
        if (isset($forminput->timelimit)) $this->field->timelimit = $forminput->timelimit;
        if (isset($forminput->approval)) $this->field->approval = $forminput->approval;
        if (isset($forminput->comments)) $this->field->comments = $forminput->comments;
        if (isset($forminput->entryrating)) $this->field->entryrating = $forminput->entryrating;
        // locks
        if (isset($forminput->lockonapproval)) $this->field->lockonapproval = $forminput->lockonapproval;
        if (isset($forminput->lockoncomments)) $this->field->lockoncomments = $forminput->lockoncomments;
        if (isset($forminput->lockonratings)) $this->field->lockonratings = $forminput->lockonratings;
        
        if (isset($forminput->singleedit)) $this->field->singleedit = $forminput->singleedit;
        if (isset($forminput->singleview)) $this->field->singleview = $forminput->singleview;

        return true;
    }

    /**
     * Update a field in the database
     */
    public function update_field() {
        // update entry settings in the dataform
        $entrysettings = new object();
        $entrysettings->id = $this->df->id();
        $entrysettings->entriesrequired = $this->field->entriesrequired;
        $entrysettings->entriestoview = $this->field->entriestoview;
        $entrysettings->maxentries = $this->field->maxentries;
        $entrysettings->timelimit = $this->field->timelimit;
        $entrysettings->approval = $this->field->approval;
        $entrysettings->comments = $this->field->comments;
        $entrysettings->entryrating = $this->field->entryrating;
        $entrysettings->locks = $this->field->lockonapproval | $this->field->lockoncomments | $this->field->lockonratings;
        $entrysettings->singleedit = $this->field->singleedit;
        $entrysettings->singleview = $this->field->singleview;
        
        
        if (!update_record('dataform', $entrysettings)) {
            notify('updating of entry settings failed!');
            return false;
        }
        return true;
    }

    /**
     * 
     */
    public function insert_field() {
        return false;
    }

    /**
     * 
     */
    public function delete_field() {
        return false;
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
    public function patterns($entry = 0, $edit = false, $enabled = false) {
        global $CFG;
        
        $patterns = array(
            'actions' => array(),
            'reference' => array(),
            'entryinfo' => array()
        );
        
        if (!$entry) { // new entry (0) displays nothing
            $patterns['actions']['##edit##'] = '';
            $patterns['actions']['##delete##'] = '';
            $patterns['actions']['##select##'] = '';

            $patterns['reference']['##more##'] = '';
            $patterns['reference']['##moreurl##'] = '';

            $patterns['entryinfo']['##entryid##'] = '';

        } else {  // no edit mode for this field
            // reference
            if ($this->field->singleview) {
                $baseurl = preg_replace('/([\s\S]+)view=\d+([\s\S]*)/', '$1view='. $this->field->singleview. '$2', $entry->baseurl);
            } else {
                $baseurl = $entry->baseurl;
            }
            $moreurl = $baseurl. '&amp;rid='. $entry->id;
            $patterns['reference']['##more##'] = '<a href="' . $moreurl . '"><img src="' . $CFG->pixpath . '/i/search.gif" class="iconsmall" alt="' . get_string('more', 'dataform') . '" title="' . get_string('more', 'dataform') . '" /></a>';
            $patterns['reference']['##moreurl##'] = $moreurl;

            // TODO: should allow selecting for duplicating purposes
            $patterns['actions']['##select##'] = !$enabled ? '' : '<input type="checkbox" name="selector_'. $entry->id. '" />';

            // edit
            if ($this->field->singleedit) {
                $baseurl = preg_replace('/([\s\S]+)view=\d+([\s\S]*)/', '$1view='. $this->field->singleedit. '$2', $entry->baseurl). '&amp;rid='. $entry->id;
            } else {
                $baseurl = $entry->baseurl;
            }
            $editurl = $baseurl. '&amp;editentries='. $entry->id. '&amp;sesskey='. sesskey();
            $patterns['actions']['##edit##'] = !$enabled ? '' : '<a href="'. $editurl. '"><img src="'. $CFG->pixpath. '/t/edit.gif" class="iconsmall" alt="'. get_string('edit', 'dataform'). '" title="'. get_string('edit', 'dataform'). '" /></a>';

            // delete
            $patterns['actions']['##delete##'] = !$enabled ? '' : '<a href="'. $entry->baseurl. '&amp;delete='. $entry->id. '&amp;sesskey='. sesskey(). '"><img src="'. $CFG->pixpath. '/t/delete.gif" class="iconsmall" alt="'. get_string('delete', 'dataform'). '" title="'. get_string('delete', 'dataform'). '" /></a>';

            // entry info
            $patterns['entryinfo']['##entryid##'] = $entry->id;
        }        
            
        return $patterns;
    }
            
    /**
     * 
     */
    public function display_search($mform, $i) {
        return '';
    }
    
    /**
     * 
     */
    public function get_search_sql($search) {
        return '';
    }

    /**
     * 
     */
    public function parse_search($formdata, $i) {
        return false;
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return '';
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return false;
    }

    /**
     *
     */
    public function export_text_value($entry) {
        return $entry->id;
    }

}
?>