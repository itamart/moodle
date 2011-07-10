<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__comment extends dataform_field_base {

    public $type = '_comment';

    /**
     * 
     */
    public function dataform_field__comment($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        $patterns = array('comments' => array());

        $patterns['comments']['##comments:showinline##'] = '';
        $patterns['comments']['##comments:showinpopup##'] = '';
        //$patterns['comments']['##comments:addinline##'] = '';
        $patterns['comments']['##comments:addinpopup##'] = '';
        $patterns['comments']['##comments:showaddinpopup##'] = '';
        // if no record display nothing
        // no edit mode for this field
        if ($record and $this->df->data->comments) {
            $recordid = $record->id;
            $fieldid = $this->field->id;
            if ($comments = count_records('dataform_comments', 'recordid', $record->id)) {
                $strcomments = get_string('commentsn', 'dataform', $comments);
                $patterns['comments']['##comments:showinline##'] = $this->display_browse($record->id);
                $patterns['comments']['##comments:showinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=1", 'comments', $strcomments, 400, 600, null, null, true));
                //$patterns['comments']['##comments:addinline##'] = $this->display_edit($record->id, false, true);
                $patterns['comments']['##comments:addinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;edit=1", 'comments', get_string('commentadd', 'dataform'), 400, 600, null, null, true));
                $patterns['comments']['##comments:showaddinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=1&amp;edit=1", 'comments', $strcomments, 400, 600, null, null, true));
            } else {
                $strcomments = get_string('commentsnone', 'dataform');
                $patterns['comments']['##comments:showinline##'] = $strcomments;
                $patterns['comments']['##comments:showinpopup##'] = $strcomments;
                $patterns['comments']['##comments:showaddinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=1&amp;edit=1", 'comments', $strcomments, 400, 600, null, null, true));
            }
        }
        
        return $patterns;
    }

    /**
     * 
     */
    public function activity_patterns() {
        
        $patterns = array();
            
        return $patterns;
    }

    /**
     * 
     */
    public function display_popup($record = 0, $params = null) {
        $recordid = $record ? $record->id : 0;

        if (isset($params['show'])) {
            return $this->display_browse($recordid, true);
        } else if (isset($params['edit'])) {
            return $this->display_edit($recordid);
        } else {
            return '';
        }
    }

    /**
     * prints all comments + a text box for adding additional comment
     */
    protected function display_browse($recordid, $popup = false) {
        global $USER, $CFG;

        $str = '';
        if ($comments = get_records('dataform_comments','recordid',$recordid)) {
            foreach ($comments as $comment) {
                $stredit = get_string('edit');
                $strdelete = get_string('delete');
                $user = get_record('user','id',$comment->userid);
                
                $str .= '<table cellspacing="0" align="center" class="dataformcomment forumpost">';
                $str .= '<tr class="header"><td class="picture left">';
                $str .= print_user_picture($user, $this->df->course->id, $user->picture, 0, true);
                $str .= '</td>';
                $str .= '<td class="topic starter" align="left"><div class="author">';
                $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $this->df->context));
                $by = new object();
                $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.
                            $user->id.'&amp;course='.$this->df->course->id.'">'.$fullname.'</a>';
                $by->date = userdate($comment->modified);
                $str .= get_string('bynameondate', 'dataform', $by);
                $str .= '</div></td></tr>';
                $str .= '<tr><td class="left side">';
                if ($groups = groups_get_all_groups($this->df->course->id, $comment->userid, $this->df->cm->groupingid)) {
                    $str .= print_group_picture($groups, $this->data->course, false, true, true);
                } else {
                    $str .= '&nbsp;';
                }

                // Actual content
                $str .= '</td><td class="content" align="left">'."\n";
                // Print whole message
                $str .= format_text($comment->content, $comment->format);

                // Commands
                $str .= '<div class="commands">';
                // TODO: is_entry_owner requires recordid
                if ($this->df->user_is_entry_owner($comment->recordid) or has_capability('mod/dataform:managecomments', $this->df->context)) {
                    //$str .= '<a href="'.$CFG->wwwroot.'/mod/dataform/comment.php?rid='.$comment->recordid.'&amp;mode=edit&amp;commentid='.$comment->id.'&amp;page='.$page.'">'.$stredit.'</a>';
                    if ($popup) {
                        $edit = optional_param('edit', 0, PARAM_BOOL);
                        $str .= '<a href="'.$CFG->wwwroot.'/mod/dataform/popup.php?rid='. $recordid. '&amp;fid='. $this->field->id. '&amp;show=1&amp;edit='. $edit. '&amp;delete='. $comment->id. '&amp;sesskey='. sesskey(). '">'.$strdelete.'</a>';
                    } else {
                        $str .= '<a href="'.$CFG->wwwroot.'/mod/dataform/view.php?d='. $this->df->id(). '&amp;deletecomment='. $comment->id. '&amp;sesskey='. sesskey(). '">'.$strdelete.'</a>';
                    }
                }

                $str .= '</div>';
                $str .= '</td></tr></table>'."\n\n";
            }
        }
        return $str;
    }   

    /**
     * 
     */
    protected function display_edit($recordid, $htmleditor = true, $savebutton = false) {
        $str = '';
        
        $cancomment = has_capability('mod/dataform:comment', $this->df->context);
        if (isloggedin() and !isguest() and $cancomment) {

            $str .= print_textarea($htmleditor, 7, 15, 0, 0, 'field_comment', '', 0, true);
            
        }

        if ($savebutton) {
            $str .= '<div style="text-align:center">'.
                '<input type="submit" name="saveandview" value="'. get_string('saveandview','dataform'). '" />'.
                '</div>';
        }
        return $str;
    }    

    /**
     * TODO
     */
    public function get_search_sql($value = '') {
        return '';
    }

    /**
     * TODO: use join?
     */
    public function get_sort_sql() {
        return "(Select count(recordid) From mdl_dataform_comments as cm Where cm.recordid = r.id)";
    }

    /**
     * 
     */
    public function update_content($recordid, $value='', $name='') {
        global $CFG, $USER;

        $comment = new object();
        if ($commentid = optional_param('commentid', 0, PARAM_INT)) {
            $comment->id       = $commentid;
            $comment->content  = $value;
            $comment->format   = $formadata->format;
            $comment->modified = time();
            update_record('dataform_comments',$comment);
    
        // new comment
        } else {
            $comment->userid   = $USER->id;
            $comment->created  = time();
            $comment->modified = time();
            $comment->content  = $value;
            $comment->recordid = $recordid;
            insert_record('dataform_comments',$comment);
        }
    }
    
    /**
     * Delete all content associated with the field
     */
    public function delete_content($recordid = 0, $commentid = 0) {
        if ($commentid) {
            delete_records('dataform_comments', 'id', $commentid);
        } else if ($recordid) {
            delete_records('dataform_comments', 'recordid', $recordid);
        }
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return false;
    }

    /**
     * returns an array of distinct content of the field
     */
    public function print_after_form() {
        if (can_use_richtext_editor()) {
            use_html_editor('field_comment', '', 'edit-field_comment');
        }
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
}
?>