<?php  // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_file extends dataform_field_base {
    public $type = 'file';

    /**
     *
     */
    function dataform_field_file($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * content: "a##b" where a is the file name, b is the display name
     */
    public function update_content($recordid, $value, $name) {
        global $CFG;

        if (!$oldcontent = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
            // Quickly make one now!
            $oldcontent = new object;
            $oldcontent->fieldid = $this->field->id;
            $oldcontent->recordid = $recordid;
            if (!$oldcontent->id = insert_record('dataform_contents', $oldcontent)) {
                error('Could not make an empty record!');
            }
        }
        $content = new object;
        $content->id = $oldcontent->id;
        $names = explode('_',$name);
        switch ($names[3]) {
            case 'file':
                // file just uploaded
                $filename = $_FILES[$names[0].'_'.$names[1].'_'.$names[2]];
                $filename = $filename['name'];
                $dir = $this->df->course->id.'/'.$CFG->moddata.'/dataform/'.$this->df->id().'/'.$this->field->id.'/'.$recordid;
                // only use the manager if file is present, to avoid "are you sure you selected a file to upload" msg
                if ($filename){
                    require_once($CFG->libdir.'/uploadlib.php');

                    $um = new upload_manager($names[0].'_'.$names[1].'_'.$names[2],true,false,$this->df->course->id,false,$this->field->param3);
                    if ($um->process_file_uploads($dir)) {
                        $newfile_name = $um->get_new_filename();
                        $content->content = $newfile_name;
                        update_record('dataform_contents',$content);
                    }
                }
                break;

            case 'filename':
                // only changing alt tag
                $content->content1 = clean_param($value, PARAM_NOTAGS);
                update_record('dataform_contents', $content);
                break;

            default:
                break;
        }
    }

    /**
     *
     */
    function notemptyfield($value, $name) {
        $names = explode('_',$name);
        if ($names[3] == 'file') {
            $filename = $_FILES[$names[0].'_'.$names[1].'_'.$names[2]];
            return !empty($filename['name']);
            // if there's a file in $_FILES, not empty
        }
        return false;
    }

    /**
     *
     */
    public function export_text_supported() {
        return false;
    }

    /**
     *
     */
    public function import_text_supported() {
        return false;
    }

    /**
     *
     */
    protected function display_edit($recordid = 0) {
        global $CFG;
        if ($recordid){
            if ($content = get_record('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid)) {
                $contents[0] = $content->content;
                $contents[1] = $content->content1;
            } else {
                $contents[0] = '';
                $contents[1] = '';
            }
            $src         = empty($contents[0]) ? '' : $contents[0];
            $name        = empty($contents[1]) ? $src : $contents[1];
            $displayname = empty($contents[1]) ? '' : $contents[1];
            require_once($CFG->libdir.'/filelib.php');
            $source = get_file_url($this->df->course->id.'/'.$CFG->moddata.'/dataform/'.$this->df->id().'/'.$this->field->id.'/'.$recordid);
        } else {
            $src = '';
            $name = '';
            $displayname = '';
            $source = '';
        }
        $str = '<div title="' . s($this->field->description) . '">';
        $str .= '<fieldset><legend><span class="accesshide">'.$this->field->name.'</span></legend>';
        $str .= '<input type="hidden" name ="field_'.$this->field->id. '_'. $recordid.'_file'. '" value="fakevalue" />';
        $str .= get_string('file','dataform'). ' <input type="file" name ="field_'.$this->field->id. '_'. $recordid. '" id="field_'.
                            $this->field->id. '_'. $recordid.'" title="'.s($this->field->description).'" /><br />';
        $str .= get_string('optionalfilename','dataform').' <input type="text" name="field_' .$this->field->id. '_'. $recordid.'_filename'. '"
                            id="field_'.$this->field->id. '_'. $recordid.'_filename'. '" value="'.s($displayname).'" /><br />';
        $str .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.s($this->field->param3).'" />';
        $str .= '</fieldset>';
        $str .= '</div>';
        if ($recordid and isset($content) and !empty($content->content)) {
            // Print icon
            require_once($CFG->libdir.'/filelib.php');
            $icon = mimeinfo('icon', $src);
            $str .= '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                    '<a href="'.$source.'/'.$src.'" >'.$name.'</a>';
        }
        return $str;
    }

    /**
     *
     */
    protected function display_browse($recordid) {
        global $CFG;
        if (!$content = get_record('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid)) {
            return false;
        }
        $width = $this->field->param1 ? ' width = "'.s($this->field->param1).'" ':' ';
        $height = $this->field->param2 ? ' height = "'.s($this->field->param2).'" ':' ';
        if (empty($content->content)) {
            return '';
        }
        require_once($CFG->libdir.'/filelib.php');
        $src  = $content->content;
        $name = empty($content->content1) ? $src : $content->content1;
        $source = get_file_url($this->df->course->id.'/'.$CFG->moddata.'/dataform/'.$this->df->id().'/'.$this->field->id.'/'.$recordid);
        $icon = mimeinfo('icon', $src);
        $str = '<img src="'.$CFG->pixpath.'/f/'.$icon.'" height="16" width="16" alt="'.$icon.'" />&nbsp;'.
                        '<a href="'.$source.'/'.$src.'" >'.$name.'</a>';
        return $str;
    }

}
?>