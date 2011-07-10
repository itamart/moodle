<?php  // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_url extends dataform_field_base {
    public $type = 'url';

    function dataform_field_text($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     *
     */
    public function update_content($recordid, $value='', $name='') {
        $content = new object;
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        if ($name) {
            $names = explode('_', $name);
            switch ($names[3]) {
                case 0:
                    // update link
                    $content->content = clean_param($value, PARAM_URL);
                    break;
                case 1:
                    // add text
                    $content->content1 = clean_param($value, PARAM_NOTAGS);
                    break;
                default:
                    break;
            }

        // update from csv    
        } else if (strpos($value, '##') !== false) {
            $value = explode('##', $value);
            $content->content = clean_param($value[0], PARAM_URL);
            $content->content1 = clean_param($value[1], PARAM_NOTAGS);
        } else {
            $content->content = clean_param($value, PARAM_URL);
        }

        // TODO do not update or insert if no value
        if ($oldcontent = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
            $content->id = $oldcontent->id;
            return update_record('dataform_contents', $content);
        } else {
            return insert_record('dataform_contents', $content);
        }
    }

    /**
     *
     */
    public function notemptyfield($value, $name) {
        $names = explode('_',$name);
        $value = clean_param($value, PARAM_URL);
        //clean first
        if ($names[3] == '0') {
            return ($value!='http://' && !empty($value));
        }
        return false;
    }

    /**
     *
     */
    public function export_text_value($content) {
        $exporttext = $content->content;
        if ($content->content1) {
            $exporttext .= "##$content->content1";
        }
        return $exporttext;
    }

    /**
     *
     */
    protected function display_edit($recordid = 0) {
        global $CFG;
        $url = '';
        $text = '';
        if ($recordid) {
            if ($content = get_record('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid)) {
                $url  = $content->content;
                $text = $content->content1;
            }
        }
        $url = empty($url) ? 'http://' : $url;
        $str = '<div title="'.s($this->field->description).'">';
        if (!empty($this->field->param1) and empty($this->field->param2)) {
            $str .= '<table><tr><td align="right">';
            $str .= get_string('url','dataform').':</td>'.
                    '<td><input type="text" name="field_'. $this->field->id. '_'. $recordid. '_0'. '" id="field_'. $this->field->id. '_'. $recordid. '_0'. '" value="'.$url.'" size="60" /></td></tr>';
            $str .= '<tr><td align="right">'. get_string('text','dataform'). ':</td>'.
                    '<td><input type="text" name="field_'. $this->field->id. '_'. $recordid. '_1'. '" id="field_'. $this->field->id. '_'. $recordid. '_1'. '" value="'.s($text).'" size="60" /></td></tr>';
            $str .= '</table>';
        } else {
            // Just the URL field
            $str .= '<input type="text" name="field_'.$this->field->id. '_'. $recordid.'_0'. '" id="field_'. $this->field->id. '_'. $recordid. '_0'. '" value="'.s($url).'" size="60" />';
        }
        $str .= '</div>';
        return $str;
    }

    /**
     *
     */
    protected function display_browse($recordid) {
        if ($content = get_record('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid)) {
            $url = empty($content->content)? '':$content->content;
            $text = empty($content->content1)? '':$content->content1;
            if (empty($url) or ($url == 'http://')) {
                return '';
            }
            if (!empty($this->field->param2)) {
                // param2 forces the text to something
                $text = $this->field->param2;
            }
            if ($this->field->param1) {
                // param1 defines whether we want to autolink the url.
                if (!empty($text)) {
                    $str = '<a href="'.$url.'">'.$text.'</a>';
                } else {
                    $str = '<a href="'.$url.'">'.$url.'</a>';
                }
            } else {
                $str = $url;
            }
            return $str;
        }
        return false;
    }
}
?>