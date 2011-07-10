<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__rating extends dataform_field_base {

    public $type = '_rating';

    /**
     * 
     */
    public function dataform_field__rating($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        global $CFG, $USER;    
        
        $patterns = array('ratings' => array());
        // TODO
        $aggregations = array('avg' => 'average', 'sum' => 'sum', 'max' => 'max', 'min' => 'min');

        $patterns['ratings']['##ratings:count##'] = '';
        foreach (array_keys($aggregations) as $aggregation) {
            $patterns['ratings']["##ratings:$aggregation##"] = '';        
        }
        $patterns['ratings']['##ratings:viewinline##'] = '';
        $patterns['ratings']['##ratings:viewinpopup##'] = '';
        //$patterns['ratings']['##ratings:rateinline##'] = '';
        $patterns['ratings']['##ratings:rateinpopup##'] = '';
        $patterns['ratings']['##ratings:viewrateinpopup##'] = '';
        // if no record display nothing
        // no edit mode for this field
        if ($record and $this->df->data->entryrating) {
            $recordid = $record->id;
            $fieldid = $this->field->id;
            $show = $edit = '';
            // permissions
            if (has_capability('mod/dataform:managetemplates', $this->df->context)) {
                $show = '&amp;show=1';
                // TODO remove
                $edit = '&amp;edit=1';
            } else {
                $user_is_entry_owner = $this->df->user_is_entry_owner($record->userid);
                if (has_capability('mod/dataform:rateentry', $this->df->context) and !$user_is_entry_owner) {
                    $edit = '&amp;edit=1';
                }
                if (has_capability('mod/dataform:viewentryrating', $this->df->context) or $user_is_entry_owner) {
                    $show = '&amp;show=1';
                }
            }
            if ($show or $edit) {
                if ($ratingscount = count_records('dataform_ratings', 'recordid', $record->id)) {
                    $patterns['ratings']['##ratings:count##'] = $ratingscount;
                    $sqlratings = "SELECT u.*, r.rating FROM {$CFG->prefix}dataform_ratings r, {$CFG->prefix}user u
                                    WHERE r.recordid = $recordid AND r.userid = u.id ORDER BY u.firstname ASC";
                    $ratings = get_records_sql($sqlratings);
                    $scale = make_grades_menu($this->df->data->entryrating);
                    foreach ($aggregations as $aggregation => $name) {
                        $patterns['ratings']["##ratings:$aggregation##"] = $this->get_ratings_aggregate($record->id, $ratings, $scale, $aggregation, false);
                    }
                    if (!$edit) {
                        //$patterns['ratings']['##ratings:showinline##'] = $this->display_browse($record->id);
                        $patterns['ratings']['##ratings:viewinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=1", 'ratings', get_string('ratingsview', 'dataform'), 400, 600, null, null, true));
                        $patterns['ratings']['##ratings:viewrateinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid$show$edit", 'ratings', get_string('ratingsviewrate', 'dataform'), 400, 600, null, null, true));
                        $patterns['ratings']['##ratings:viewrateinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid$show$edit", 'ratings', get_string('ratingsview', 'dataform'), 400, 600, null, null, true));
                    } else {
                        $patterns['ratings']['##ratings:viewrateinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid$show$edit", 'ratings', get_string('ratingsviewrate', 'dataform'), 400, 600, null, null, true));
                    }

                } else {
                    $patterns['ratings']['##ratings:count##'] = '---';
                    foreach ($aggregations as $aggregation => $name) {
                        $patterns['ratings']["##ratings:$aggregation##"] = '---';
                    }
                    $strratings = get_string('ratingsnone', 'dataform');
                    $patterns['ratings']['##ratings:viewinline##'] = $strratings;
                    $patterns['ratings']['##ratings:viewinpopup##'] = $strratings;
                    if ($edit) {
                        $patterns['ratings']['##ratings:viewrateinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=1&amp;edit=1", 'ratings', $strratings, 400, 600, null, null, true));
                    } else {
                        $patterns['ratings']['##ratings:viewrateinpopup##'] = $strratings;
                    }
                }
                if ($edit) {
                    //$patterns['ratings']['##ratings:rateinline##'] = $this->display_edit($record->id, false, true);
                    $patterns['ratings']['##ratings:rateinpopup##'] = str_replace(',', '&#44;', link_to_popup_window("/mod/dataform/popup.php?rid=$recordid&amp;fid=$fieldid&amp;edit=1", 'ratings', get_string('rate', 'dataform'), 400, 600, null, null, true));
                }
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
        if ($record) {
            $recordid = $record->id;

            if (isset($params['show'])) {
                if (has_capability('mod/dataform:managetemplates', $this->df->context)
                            or has_capability('mod/dataform:viewentryrating', $this->df->context)
                            or $this->df->user_is_entry_owner($record->userid)) {
                    $sort = isset($params['sort']) ? $params['sort'] : '';
                    return $this->display_browse($recordid, null, $sort);
                }
                
            } else if (isset($params['edit'])) {
                if (has_capability('mod/dataform:managetemplates', $this->df->context)
                            or (has_capability('mod/dataform:rateentry', $this->df->context) and !$this->df->user_is_entry_owner($record->userid))) {
                    return $this->display_edit($recordid);
                }
            }
        }
        
        return '';
    }

    /**
     * Print the multiple ratings on a post given to the current user by others.
     * Scale is an array of ratings
     */
    protected function display_browse($recordid, $ratings = null, $sort = '') {
        global $CFG;

        $str = '';
               
        if (!$ratings) {
            // get a list of ratings with raters info for a particular entry - sorted.
            switch ($sort) {
                case 'firstname': $sqlsort = "u.firstname ASC"; break;
                case 'rating':    $sqlsort = "r.rating ASC"; break;
                default:          $sqlsort = "r.id ASC";
            }

            $scalemenu = make_grades_menu($this->df->data->entryrating);

            $strratings = get_string('ratings', 'dataform');
            $strrating  = get_string('rating', 'dataform');
            $strname    = get_string('name');


            $sqlratings = "SELECT u.*, r.rating FROM {$CFG->prefix}dataform_ratings r, {$CFG->prefix}user u
                            WHERE r.recordid = $recordid AND r.userid = u.id ORDER BY $sqlsort";
            $ratings = get_records_sql($sqlratings);
        }
        
        if ($ratings) {
            $fieldid = $this->field->id;
        
            $str .= "<table border=\"0\" cellpadding=\"3\" cellspacing=\"3\" class=\"generalbox\" style=\"width:100%\">";
            $str .= "<tr>";
            $str .= "<th class=\"header\" scope=\"col\">&nbsp;</th>";
            $str .= "<th class=\"header\" scope=\"col\"><a href=\"popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=1&amp;sort=firstname\">$strname</a></th>";
            $str .= "<th class=\"header\" scope=\"col\" style=\"width:100%\"><a href=\"popup.php?rid=$recordid&amp;fid=$fieldid&amp;show=1&amp;sort=rating\">$strrating</a></th>";
            $str .= "</tr>";
            foreach ($ratings as $rating) {
                if (has_capability('mod/dataform:manageentries', $this->df->context)) {
                    $str .= '<tr class="forumpostheadertopic">';
                } else {
                    $str .= '<tr class="forumpostheader">';
                }
                $str .= '<td class="picture">';
                $str .= print_user_picture($rating->id, $this->df->data->course, $rating->picture, false, true, true);
                $str .= '</td>';
                $str .= '<td class="author"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$rating->id.'&amp;course='.$this->df->data->course.'">'.fullname($rating).'</a></td>';
                if (array_key_exists($rating->rating, $scalemenu)) {
                    $str .= '<td style="white-space:nowrap" align="center" class="rating">'.$scalemenu[$rating->rating].'</td>';
                } else {
                    $str .= '<td style="white-space:nowrap" align="center" class="rating">'.$rating->rating.'</td>';
                }
                $str .= "</tr>\n";
            }
            $str .= "</table>";
            $str .= "<br />";
        } else {
            $str .= get_string('ratingsnone', 'dataform');
        }

        return $str;
    }   

    /**
     * Print the menu of ratings as part of a larger form
     *
     * @param int $record The entry to rate. 0 for activity rating
     * @param bool $rateactivity
     */
    protected function display_edit($recordid, $rateactivity = false, $form = false) {
        global $CFG, $USER;

        $context = $this->df->context;
        $str = '<div style="text-align:center">';
        $scaleid = 0;
        static $strrate;
        if (empty($strrate)) {
            $strrate = get_string("rate", "dataform");
        }

        if ($rateactivity) {
            // TODO
            if ($this->df->data->rating and has_capability('mod/dataform:managetemplates', $context)) {
                $scaleid = $this->df->data->rating;
                if ($ratingsscale = make_grades_menu($scaleid)) {
                    // get rater's previous rating if any
                    if (!$rating = get_record("dataform_ratings", "userid", $recordid, "recordid", 0)) {
                        $rating->rating = -999;
                    }
                    // add the rating menu form
                    $str .= popup_form("$CFG->wwwroot/mod/dataform/popup.php?rid=$recordid&amp;rate=", $ratingsscale, "rateform$recordid", $rating->rating, "$strrate...", '', '', true);
                }
            }
        } else {
            $scaleid = $this->df->data->entryrating;
            if ($ratingsscale = make_grades_menu($scaleid)) {
                // get rater's previous rating if any
                if (!$rating = get_record("dataform_ratings", "userid", $USER->id, "recordid", $recordid)) {
                    $rating->rating = -999;
                } else {
                    // register the rating id in the form
                    $str .= '<input type="hidden" name="ratingid" value="'. $rating->id. '" />';
                }
                
                // add the rating menu form
                $str .= choose_from_menu($ratingsscale, 'field_'.$this->field->id. '_'. $recordid, $rating->rating,
                                         get_string('rate', 'dataform'), '', '', true, false, 0, 'field_'.$this->field->id. '_'. $recordid);
                //$str .= popup_form("$CFG->wwwroot/mod/dataform/popup.php?rid=$recordid&amp;rate=", $ratingsscale, "rateform$recordid", $rating->rating, "$strrate...", '', '', true);
            }
        }
        // add help for userdefined scale
        if ($scaleid < 0) {
            if ($scale = get_record('scale', 'id', abs($scaleid))) {
                $str .= print_scale_menu_helpbutton($this->df->data->course, $scale, true);
            }
        }
        $str .= '</div>';
        
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
        return "(Select count(recordid) From mdl_dataform_ratings as cr Where cr.recordid = r.id)";
    }

    /**
     * 
     */
    public function update_content($recordid, $value='', $name='') {
        global $USER;

        $rating = new object();
        // update existing rating
        if ($ratingid = optional_param('ratingid', 0, PARAM_INT)) {
            $rating->id     = $ratingid;
            $rating->rating = $value;
            update_record('dataform_ratings',$rating);
    
        // add new rating
        } else {
            $rating->userid   = $USER->id;
            $rating->recordid = $recordid;
            $rating->rating  = $value;
            insert_record('dataform_ratings',$rating);
        }
    }
    
    /**
     * Delete all content associated with the field
     */
    public function delete_content($recordid = 0, $ratingid = 0) {
        if ($ratingid) {
            delete_records('dataform_ratings', 'id', $ratingid);
        } else if ($recordid) {
            delete_records('dataform_ratings', 'recordid', $recordid);
        }
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return false;
    }

    /**
     * Return the mean rating of a post given to the current user by others.
     * Scale is an array of possible ratings in the scale
     * Ratings is an optional simple array of actual ratings (just integers)
     */
    public function get_ratings_aggregate($recordid, $ratings = null, $scale = null, $aggregate = 'avg', $round = false) {
        global $CFG;
        if ($ratings or $ratings = get_records("dataform_ratings", "recordid", $recordid)) {
            $rates = array();
            foreach ($ratings as $rating) {
                $rates[] = $rating->rating;
            }
        }
        
        $ratingvalue = '';
        if ($count = count($rates)) {
            switch ($aggregate) {
                case 'avg':
                    $ratingvalue = array_sum($rates) / $count;
                    break;

                case 'sum':
                    $ratingvalue = array_sum($rates);
                    break;

                case 'max':
                    sort($rates, SORT_NUMERIC);
                    $ratingvalue = end($rates);
                    break;

                case 'min':
                    sort($rates, SORT_NUMERIC);
                    $ratingvalue = reset($rates);
                    break;
            }
            
            if (!$scale) {
                $scale = make_grades_menu($this->df->data->entryrating);
            }
        
            if ($scale and ($ratingvalue < count($scale))) {
                $ratingvalue = $scale[round($ratingvalue)];
            } else if ($round) {
                $ratingvalue = round($ratingvalue);
            }
        }
        return $ratingvalue;
    }

    /**
     *
     */
    function export_text_supported() {
        return false;
    }

    /**
     *
     */
    function import_text_supported() {
        return false;
    }
}
?>