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

        // if no record display nothing
        // no edit mode for this field
        if ($record) {  
            $patterns['entryinfo']['##'. $this->field->name. '##'] = userdate($record->{$this->field->name});
        }
        
        return $patterns;
    }

    /**
     * 
     */
    public function display_search($value = 0) {
        $valuefrom = $valueto = 0;
        if ($value) {
            $value = explode('$', $value);
            $valuefrom = $value[0];
            $valueto = isset($value[1]) ? $value[1] : 0;
        }
        $str = 'From:&nbsp;'.
                print_date_selector('f_'. $this->field->id. '_d_from', 'f_'. $this->field->id. '_m_from', 'f_'. $this->field->id. '_y_from', $valuefrom, true).
                print_time_selector('f_'. $this->field->id. '_h_from', 'f_'. $this->field->id. '_n_from', $valuefrom, 1, true).
                '<br />To:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
                print_date_selector('f_'. $this->field->id. '_d_to', 'f_'. $this->field->id. '_m_to', 'f_'. $this->field->id. '_y_to', $valueto, true).
                print_time_selector('f_'. $this->field->id. '_h_to', 'f_'. $this->field->id. '_n_to', $valueto, 1, true);
        return $str;
    }
    
    /**
     * 
     */
    public function get_search_sql($value) {
        return " (r.{$this->field->name} >= '$valuefrom' AND r.{$this->field->name} <= '$valueto') "; 
    }

    /**
     * 
     */
    public function parse_search() {
        // time from
        $timefrom = 0;
        $minute   = optional_param('f_'.$this->field->id.'_n_from', 0, PARAM_INT);
        $hour = optional_param('f_'.$this->field->id.'_h_from', 0, PARAM_INT);
        $day   = optional_param('f_'.$this->field->id.'_d_from', 0, PARAM_INT);
        $month = optional_param('f_'.$this->field->id.'_m_from', 0, PARAM_INT);
        $year  = optional_param('f_'.$this->field->id.'_y_from', 0, PARAM_INT);
        if (!empty($minute) && !empty($hour) && !empty($day) && !empty($month) && !empty($year)) {
            $timefrom = make_timestamp($year, $month, $day, $hour, $minute, 0, 0, false);
        }
        $timeto = 0;
        $minute   = optional_param('f_'.$this->field->id.'_n_to', 0, PARAM_INT);
        $hour = optional_param('f_'.$this->field->id.'_h_to', 0, PARAM_INT);
        $day   = optional_param('f_'.$this->field->id.'_d_to', 0, PARAM_INT);
        $month = optional_param('f_'.$this->field->id.'_m_to', 0, PARAM_INT);
        $year  = optional_param('f_'.$this->field->id.'_y_to', 0, PARAM_INT);
        if (!empty($minute) && !empty($hour) && !empty($day) && !empty($month) && !empty($year)) {
            $timeto = make_timestamp($year, $month, $day, $hour, $minute, 0, 0, false);
        }
        return $timefrom. '$'. $timeto;
    }

    /**
     * 
     */
    public function update_content($recordid, $value, $name='') {
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return 'r.'. $this->field->name;
    }

    /**
     * 
     */
    public function get_ratings($recordid, $sort="u.firstname ASC") {
    // Returns a list of ratings for a particular post - sorted.
        global $CFG;
        return get_records_sql("SELECT u.*, r.rating
                                  FROM {$CFG->prefix}dataform_ratings r,
                                       {$CFG->prefix}user u
                                 WHERE r.recordid = $recordid
                                   AND r.userid = u.id
                                 ORDER BY $sort");
    }

    /**
     * 
     */
    public function get_ratings_mean($recordid, $scale, $ratings=NULL) {
    // Return the mean rating of a post given to the current user by others.
    // Scale is an array of possible ratings in the scale
    // Ratings is an optional simple array of actual ratings (just integers)
        if (!$ratings) {
            $ratings = array();
            if ($rates = get_records("dataform_ratings", "recordid", $recordid)) {
                foreach ($rates as $rate) {
                    $ratings[] = $rate->rating;
                }
            }
        }
        $count = count($ratings);
        if ($count == 0) {
            return "";
        } else if ($count == 1) {
            return $scale[$ratings[0]];
        } else {
            $total = 0;
            foreach ($ratings as $rating) {
                $total += $rating;
            }
            $mean = round( ((float)$total/(float)$count) + 0.001);  // Little fudge factor so that 0.5 goes UP
            if (isset($scale[$mean])) {
                return $scale[$mean]." ($count)";
            } else {
                return "$mean ($count)";    // Should never happen, hopefully
            }
        }
    }

    /**
     * 
     */
    public function print_rating_menu($recordid, $userid, $scale) {
    // Print the menu of ratings as part of a larger form.
    // If the post has already been - set that value.
    // Scale is an array of ratings
        static $strrate;
        if (!$rating = get_record("dataform_ratings", "userid", $userid, "recordid", $recordid)) {
            $rating->rating = -999;
        }
        if (empty($strrate)) {
            $strrate = get_string("rate", "data");
        }
        choose_from_menu($scale, $recordid, $rating->rating, "$strrate...", '', -999);
    }

    /**
     * 
     */
    public function print_ratings($data, $record) {
        global $USER;

        $cm = get_coursemodule_from_instance('dataform', $this->id());
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        if ($this->data->assessed and !empty($USER->id) and (has_capability('mod/dataform:rate', $context) or has_capability('mod/dataform:viewrating', $context) or $this->user_is_entry_owner($record->userid))) {
            if ($ratingsscale = make_grades_menu($this->data->scale)) {
                $ratingsmenuused = false;
                echo '<div class="ratings" style="text-align:center">';
                echo '<form id="form" method="post" action="rate.php">';
                echo '<input type="hidden" name="dataid" value="'.$this->id().'" />';
                if (has_capability('mod/dataform:rate', $context) and !$this->user_is_entry_owner($record->userid)) {
                    dataform_print_ratings_mean($record->id, $ratingsscale, has_capability('mod/dataform:viewrating', $context));
                    echo '&nbsp;';
                    dataform_print_rating_menu($record->id, $USER->id, $ratingsscale);
                    $ratingsmenuused = true;
                } else {
                    dataform_print_ratings_mean($record->id, $ratingsscale, true);
                }
                if ($this->data->scale < 0) {
                    if ($scale = get_record('scale', 'id', abs($this->data->scale))) {
                        print_scale_menu_helpbutton($this->data->course, $scale );
                    }
                }

                if ($ratingsmenuused) {
                    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                    echo '<input type="submit" value="'.get_string('sendinratings', 'dataform').'" />';
                }
                echo '</form>';
                echo '</div>';
            }
        }
    }

    /**
     * 
     */
    public function print_ratings_mean($recordid, $scale, $link=true) {
    // Print the multiple ratings on a post given to the current user by others.
    // Scale is an array of ratings

        static $strrate;
        $mean = dataform_get_ratings_mean($recordid, $scale);
        if ($mean !== "") {
            if (empty($strratings)) {
                $strratings = get_string("ratings", "data");
            }
            echo "$strratings: ";
            if ($link) {
                link_to_popup_window ("/mod/dataform/report.php?id=$recordid", "ratings", $mean, 400, 600);
            } else {
                echo "$mean ";
            }
        }
    }

}
?>