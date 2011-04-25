<?php // $Id$
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999-onwards Moodle Pty Ltd  http://moodle.com          //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

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

        // if no record display nothing
        // no edit mode for this field
        if ($record and if ($df->data->comments) {
            $comments = count_records('dataform_comments', 'recordid', $record->id);
            // open edit form of comment in a single view
            $patterns['comments']['##comments:addinline##'] = '<a href="view.php?rid='.$record->id.'#comments">'.get_string('commentsn','dataform', $comments).'</a>';
            // open edit form of comment in a single view
            $patterns['comments']['##comments:addinpopup##'] = '<a href="view.php?rid='.$record->id.'#comments">'.get_string('commentsn','dataform', $comments).'</a>';
            $patterns['comments']['##comments:showinline##'] = '<a href="view.php?rid='.$record->id.'#comments">'.get_string('commentsn','dataform', $comments).'</a>';
            $patterns['comments']['##comments:showinpopup##'] = '<a href="view.php?rid='.$record->id.'#comments">'.get_string('commentsn','dataform', $comments).'</a>';
        } else {
            $patterns['comments']['##comments:addinline##'] = '';
            $patterns['comments']['##comments:addinpopup##'] = '';
            $patterns['comments']['##comments:showinline##'] = '';
            $patterns['comments']['##comments:showinline##'] = '';
        }
        
        return $patterns;
    }

    /**
     * prints all comments + a text box for adding additional comment
     */
    public function print_comments($record = 0, $page=0, $mform=false) {
        global $CFG;

        $cancomment = has_capability('mod/dataform:comment', $context);
        echo '<a name="comments"></a>';
        if ($comments = get_records('dataform_comments','recordid',$record->id)) {
            foreach ($comments as $comment) {
                $this->print_comment($comment, $page);
            }
            echo '<br />';
        }
        if (!isloggedin() or isguest() or !$cancomment) {
            return;
        }
        $editor = optional_param('addcomment', 0, PARAM_BOOL);
        if (!$mform and !$editor) {
            echo '<div class="newcomment" style="text-align:center">';
            echo '<a href="view.php?d='.$this->id().'&amp;rid='.$record->id.'&amp;mode=single&amp;addcomment=1">'.get_string('addcomment', 'dataform').'</a>';
            echo '</div>';
        } else {
            if (!$mform) {
                require_once('comment_form.php');
                $mform = new mod_dataform_comment_form('comment.php');
                $mform->set_data(array('mode'=>'add', 'page'=>$page, 'rid'=>$record->id));
            }
            echo '<div class="newcomment" style="text-align:center">';
            $mform->display();
            echo '</div>';
        }
    }

    /**
     * prints a single comment entry
     */
    public function print_comment($comment, $page=0) {
        global $USER, $CFG;

        $stredit = get_string('edit');
        $strdelete = get_string('delete');
        $user = get_record('user','id',$comment->userid);
        echo '<table cellspacing="0" align="center" width="50%" class="datacomment forumpost">';
        echo '<tr class="header"><td class="picture left">';
        print_user_picture($user, $this->data->course, $user->picture);
        echo '</td>';
        echo '<td class="topic starter" align="left"><div class="author">';
        $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $this->df->context));
        $by = new object();
        $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.
                    $user->id.'&amp;course='.$this->data->course.'">'.$fullname.'</a>';
        $by->date = userdate($comment->modified);
        print_string('bynameondate', 'dataform', $by);
        echo '</div></td></tr>';
        echo '<tr><td class="left side">';
        if ($groups = groups_get_all_groups($this->data->course, $comment->userid, $cm->groupingid)) {
            print_group_picture($groups, $this->data->course, false, false, true);
        } else {
            echo '&nbsp;';
        }

    // Actual content
        echo '</td><td class="content" align="left">'."\n";
        // Print whole message
        echo format_text($comment->content, $comment->format);

    // Commands
        echo '<div class="commands">';
        // TODO: is_entry_owner requires recordid
        if ($this->user_is_entry_owner($comment->recordid) or has_capability('mod/dataform:managecomments', $context)) {
                echo '<a href="'.$CFG->wwwroot.'/mod/dataform/comment.php?rid='.$comment->recordid.'&amp;mode=edit&amp;commentid='.$comment->id.'&amp;page='.$page.'">'.$stredit.'</a>';
                echo '| <a href="'.$CFG->wwwroot.'/mod/dataform/comment.php?rid='.$comment->recordid.'&amp;mode=delete&amp;commentid='.$comment->id.'&amp;page='.$page.'">'.$strdelete.'</a>';
        }

        echo '</div>';
        echo '</td></tr></table>'."\n\n";
    }

    
    
    
}
?>