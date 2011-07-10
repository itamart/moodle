<?php  // $Id$
//------------------------------------------------------------
// MOD FUNCTIONS WHICH ARE CALLED FROM OUTSIDE THE MODULE
//------------------------------------------------------------

/**
 * Adds an instance of a dataform
 */
function dataform_add_instance($data) {
    global $CFG;
    if (empty($data->assessed)) {
        $data->assessed = 0;
    }
    
    if ($CFG->dataform_maxentries) {
        $data->maxentries = $CFG->dataform_maxentries;
    }

    $data->timemodified = time();
    if (! $data->id = insert_record('dataform', $data)) {
        return false;
    }

    $data = stripslashes_recursive($data);
    dataform_grade_item_update($data);
    return $data->id;
}

/**
 * updates an instance of a data
 */
function dataform_update_instance($data) {
    global $CFG;
    $data->timemodified = time();
    $data->id = $data->instance;

    if (empty($data->rating)) {
        $data->rating = 0;
    }
    if (empty($data->notification)) {
        $data->notification = 0;
    }
    if (! update_record('dataform', $data)) {
        return false;
    }

    $data = stripslashes_recursive($data);
    dataform_grade_item_update($data);
    return true;
}

/**
 * deletes an instance of a data
 */
function dataform_delete_instance($id) {
    global $CFG;

    require_once('mod_class.php');

    if (!$df = new dataform($id)) {
        return false;
    }

    // delete fields and their content (including comments and ratings)
    if ($fields = $df->get_fields() or $fields = $df->get_builtin_fields()) {
        foreach ($fields as $field) {
            $field->delete_field();
        }
    }
    
    // delete views filters entries
    delete_records('dataform_views','dataid',$id);
    delete_records('dataform_filters','dataid',$id);
    delete_records('dataform_entries','dataid',$id);

    // Delete the instance itself
    $result = delete_records('dataform', 'id', $id);
    dataform_grade_item_delete($df->data);
    return $result;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the data.
 * @param $mform form passed by reference
 */
function dataform_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'dataheader', get_string('modulenameplural', 'dataform'));
    $mform->addElement('checkbox', 'reset_data', get_string('deleteallentries','dataform'));

    $mform->addElement('checkbox', 'reset_dataform_notenrolled', get_string('deletenotenrolled', 'dataform'));
    $mform->disabledIf('reset_dataform_notenrolled', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_dataform_ratings', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_dataform_comments', 'reset_data', 'checked');
}

/**
 * Course reset form defaults.
 */
function dataform_reset_course_form_defaults($course) {
    return array('reset_data'=>0, 'reset_dataform_ratings'=>1, 'reset_dataform_comments'=>1, 'reset_dataform_notenrolled'=>0);
}

/**
 * Removes all grades from gradebook
 * @param int $courseid
 * @param string optional type
 */
function dataform_reset_gradebook($courseid, $type='') {
    global $CFG;
    $sql = "SELECT d.*, cm.idnumber as cmidnumber, d.course as courseid
              FROM {$CFG->prefix}data d, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
             WHERE m.name='dataform' AND m.id=cm.module AND cm.instance=d.id AND d.course=$courseid";
    if ($datas = get_records_sql($sql)) {
        foreach ($datas as $data) {
            dataform_grade_item_update($data, 'reset');
        }
    }
}

/**
 * Actual implementation of the rest coures functionality, delete all the
 * data responses for course $data->courseid.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function dataform_reset_userdata($data) {
    global $CFG;
    require_once($CFG->libdir.'/filelib.php');
    $componentstr = get_string('modulenameplural', 'dataform');
    $status = array();
    $allrecordssql = "SELECT r.id
                        FROM {$CFG->prefix}dataform_entries r
                             INNER JOIN {$CFG->prefix}data d ON r.dataid = d.id
                       WHERE d.course = {$data->courseid}";
    $alldatassql = "SELECT d.id
                      FROM {$CFG->prefix}data d
                     WHERE d.course={$data->courseid}";
    // delete entries if requested
    if (!empty($data->reset_data)) {
        delete_records_select('dataform_ratings', "recordid IN ($allrecordssql)");
        delete_records_select('dataform_comments', "recordid IN ($allrecordssql)");
        delete_records_select('dataform_contents', "recordid IN ($allrecordssql)");
        delete_records_select('dataform_entries', "dataid IN ($alldatassql)");
        if ($datas = get_records_sql($alldatassql)) {
            foreach ($datas as $dataid=>$unused) {
                fulldelete("$CFG->dataroot/$data->courseid/moddata/dataform/$dataid");
            }
        }
        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            dataform_reset_gradebook($data->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallentries', 'dataform'), 'error'=>false);
    }

    // remove entries by users not enrolled into course
    if (!empty($data->reset_dataform_notenrolled)) {
        $recordssql = "SELECT r.id, r.userid, r.dataid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {$CFG->prefix}dataform_entries r
                              INNER JOIN {$CFG->prefix}data d ON r.dataid = d.id
                              LEFT OUTER JOIN {$CFG->prefix}user u ON r.userid = u.id
                        WHERE d.course = {$data->courseid} AND r.userid > 0";
        $course_context = get_context_instance(CONTEXT_COURSE, $data->courseid);
        $notenrolled = array();
        $fields = array();
        if ($rs = get_recordset_sql($recordssql)) {
            while ($record = rs_fetch_next_record($rs)) {
                if (array_key_exists($record->userid, $notenrolled) or !$record->userexists or $record->userdeleted
                  or !has_capability('moodle/course:view', $course_context , $record->userid)) {
                    delete_records('dataform_ratings', 'recordid', $record->id);
                    delete_records('dataform_comments', 'recordid', $record->id);
                    delete_records('dataform_contents', 'recordid', $record->id);
                    delete_records('dataform_entries', 'id', $record->id);
                    // HACK: this is ugly - the recordid should be before the fieldid!
                    if (!array_key_exists($record->dataid, $fields)) {
                        if ($fs = get_records('dataform_fields', 'dataid', $record->dataid)) {
                            $fields[$record->dataid] = array_keys($fs);
                        } else {
                            $fields[$record->dataid] = array();
                        }
                    }
                    foreach($fields[$record->dataid] as $fieldid) {
                        fulldelete("$CFG->dataroot/$data->courseid/moddata/dataform/$record->dataid/$fieldid/$record->id");
                    }
                    $notenrolled[$record->userid] = true;
                }
            }
            rs_close($rs);
            $status[] = array('component'=>$componentstr, 'item'=>get_string('deletenotenrolled', 'dataform'), 'error'=>false);
        }
    }

    // remove all ratings
    if (!empty($data->reset_dataform_ratings)) {
        delete_records_select('dataform_ratings', "recordid IN ($allrecordssql)");
        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            dataform_reset_gradebook($data->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallratings'), 'error'=>false);
    }

    // remove all comments
    if (!empty($data->reset_dataform_comments)) {
        delete_records_select('dataform_comments', "recordid IN ($allrecordssql)");
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallcomments'), 'error'=>false);
    }

    // updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('dataform', array('timeavailablefrom', 'timeavailableto', 'timeviewfrom', 'timeviewto'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }
    return $status;
}

/**
 * Returns all other caps used in module
 */
function dataform_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames');
}

//------------------------------------------------------------
// Info
//------------------------------------------------------------

/**
 * returns a list of participants of this dataform
 */
function dataform_get_participants($dataid) {
// Returns the users with data in one dataform
// (users with records in dataform_entries, dataform_comments or dataform_ratings)
    global $CFG;
    $records = get_records_sql("SELECT DISTINCT u.id, u.id
                                FROM {$CFG->prefix}user u,
                                     {$CFG->prefix}dataform_entries r
                                WHERE r.dataid = '$dataid'
                                  AND u.id = r.userid");
    $comments = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}dataform_entries r,
                                      {$CFG->prefix}dataform_comments c
                                 WHERE r.dataid = '$dataid'
                                   AND u.id = r.userid
                                   AND r.id = c.recordid");
    $ratings = get_records_sql("SELECT DISTINCT u.id, u.id
                                FROM {$CFG->prefix}user u,
                                     {$CFG->prefix}dataform_entries r,
                                     {$CFG->prefix}dataform_ratings a
                                WHERE r.dataid = '$dataid'
                                  AND u.id = r.userid
                                  AND r.id = a.recordid");
    $participants = array();
    if ($records) {
        foreach ($records as $record) {
            $participants[$record->id] = $record;
        }
    }
    if ($comments) {
        foreach ($comments as $comment) {
            $participants[$comment->id] = $comment;
        }
    }
    if ($ratings) {
        foreach ($ratings as $rating) {
            $participants[$rating->id] = $rating;
        }
    }
    return $participants;
}

/**
 * returns a summary of dataform activity of this user
 */
function dataform_user_outline($course, $user, $mod, $data) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    if ($countrecords = count_records('dataform_entries', 'dataid', $data->id, 'userid', $user->id)) {
        $result = new object();
        $result->info = get_string('numrecords', 'dataform', $countrecords);
        $lastrecord   = get_record_sql('SELECT id,timemodified FROM '.$CFG->prefix.'dataform_entries
                                         WHERE dataid = '.$data->id.' AND userid = '.$user->id.'
                                      ORDER BY timemodified DESC', true);
        $result->time = $lastrecord->timemodified;
        if ($grade) {
            $result->info .= ', ' . get_string('grade') . ': ' . $grade->str_long_grade;
        }
        return $result;
    } else if ($grade) {
        $result = new object();
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;
        $result->time = $grade->dategraded;
        return $result;
    }
    return NULL;
}

/**
 * Prints all the records uploaded by this user
 */
function dataform_user_complete($course, $user, $mod, $data) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo '<p>'.get_string('grade').': '.$grade->str_long_grade.'</p>';
        if ($grade->str_feedback) {
            echo '<p>'.get_string('feedback').': '.$grade->str_feedback.'</p>';
        }
    }
    if ($records = get_records_select('dataform_entries', 'dataid = '.$data->id.' AND userid = '.$user->id,
                                                      'timemodified DESC')) {
        dataform_print_template('singletemplate', $records, $data);
    }
}

//------------------------------------------------------------
// Participantion Reports
//------------------------------------------------------------

/**
 */
function dataform_get_view_actions() {
    return array('view');
}

/**
 */
function dataform_get_post_actions() {
    return array('add','update','record delete');
}

//------------------------------------------------------------
// Grading
//------------------------------------------------------------

/**
 * Return grade for given user or all users.
 * @return array array of grades, false if none
 */
function dataform_get_user_grades($data, $userid=0) {
    global $CFG;
    
    if ($data->ratingmethod > 0) { // aggregation of entry ratings
        $ratingmethods = array(1 => 'avg', 2 => 'sum', 3 => 'max', 4 => 'min', 5 => 'count');
        $method = $ratingmethods[$data->ratingmethod]; 
    
        $user = $userid ? "AND u.id = $userid" : "";
        $sql = "SELECT u.id, u.id AS userid, $method(drt.rating) AS rawgrade
                  FROM {$CFG->prefix}user u, {$CFG->prefix}dataform_entries dr,
                       {$CFG->prefix}dataform_ratings drt
                 WHERE u.id = dr.userid AND dr.id = drt.recordid
                       AND drt.userid != u.id AND dr.dataid = $data->id
                       $user
              GROUP BY u.id";
        return get_records_sql($sql);
    } else {
        return false;
    }
}

/**
 * Update grades by firing grade_updated event
 * @param object $data null means all databases
 * @param int $userid specific user only, 0 mean all
 * @param bool $nullifnone 
 * @param array $grades 
 */
function dataform_update_grades($data=null, $userid=0, $nullifnone=true, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($data != null) {
        if ($data->rating) {
            if ($grades or ($data->ratingmethod and $grades = dataform_get_user_grades($data, $userid))) {
                dataform_grade_item_update($data, $grades);
            } else if ($userid and $nullifnone) {
                $grade = new object();
                $grade->userid   = $userid;
                $grade->rawgrade = NULL;
                dataform_grade_item_update($data, $grade);
            } else {
                dataform_grade_item_update($data);
            }
        }
    } else {
        $sql = "SELECT d.*, cm.idnumber as cmidnumber
                  FROM {$CFG->prefix}data d, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='dataform' AND m.id=cm.module AND cm.instance=d.id";
        if ($rs = get_recordset_sql($sql)) {
            while ($data = rs_fetch_next_record($rs)) {
                if ($data->rating) {
                    dataform_update_grades($data, 0, false);
                } else {
                    dataform_grade_item_update($data);
                }
            }
            rs_close($rs);
        }
    }
}

/**
 * Update/create grade item for given data
 * @param object $data object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function dataform_grade_item_update($data, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }
    $params = array('itemname'=>$data->name, 'idnumber'=>$data->cmidnumber);
    if (!$data->rating) {
        $params['gradetype'] = GRADE_TYPE_NONE;
    } else if ($data->rating > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $data->rating;
        $params['grademin']  = 0;
    } else if ($data->rating < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$data->rating;
    }
    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/dataform', $data->course, 'mod', 'dataform', $data->id, 0, $grades, $params);
}

/**
 * Delete grade item for given data
 * @param object $data object
 * @return object grade_item
 */
function dataform_grade_item_delete($data) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    return grade_update('mod/dataform', $data->course, 'mod', 'dataform', $data->id, 0, NULL, array('deleted'=>1));
}
?>