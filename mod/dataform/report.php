<?php // $Id$

//  For a given post, shows a report of all the ratings it has

require_once("../../config.php");
require_once("lib.php");

$id   = required_param('id', PARAM_INT);
$sort = optional_param('sort', '', PARAM_ALPHA);

if (!$record = get_record('dataform_records', 'id', $id)) {
    error("Record ID is incorrect");
}

// Set a dataform object
$df = new dataform($record->dataid);
    
require_login($df->course->id, false, $df->cm);
$df->context = get_context_instance(CONTEXT_MODULE, $df->cm->id);

if (!$df->data->assessed) {
    error("This activity does not use ratings");
}

if (!df->user_is_entry_owner($record->userid) and !has_capability('mod/dataform:viewrating', $df->context) and !has_capability('mod/dataform:rate', $this->context)) {
    error("You can not view ratings");
}

switch ($sort) {
    case 'firstname': $sqlsort = "u.firstname ASC"; break;
    case 'rating':    $sqlsort = "r.rating ASC"; break;
    default:          $sqlsort = "r.id ASC";
}

$scalemenu = make_grades_menu($df->data->scale);

$strratings = get_string('ratings', 'dataform');
$strrating  = get_string('rating', 'dataform');
$strname    = get_string('name');

print_header($strratings);

if (!$ratings = $->get_ratings($record->id, $sqlsort)) {
    error("No ratings for this record!");

} else {
    echo "<table border=\"0\" cellpadding=\"3\" cellspacing=\"3\" class=\"generalbox\" style=\"width:100%\">";
    echo "<tr>";
    echo "<th class=\"header\" scope=\"col\">&nbsp;</th>";
    echo "<th class=\"header\" scope=\"col\"><a href=\"report.php?id=$record->id&amp;sort=firstname\">$strname</a></th>";
    echo "<th class=\"header\" scope=\"col\" style=\"width:100%\"><a href=\"report.php?id=$id&amp;sort=rating\">$strrating</a></th>";
    echo "</tr>";
    foreach ($ratings as $rating) {
        if (has_capability('mod/dataform:manageentries', $context)) {
            echo '<tr class="forumpostheadertopic">';
        } else {
            echo '<tr class="forumpostheader">';
        }
        echo '<td class="picture">';
        print_user_picture($rating->id, $df->data->course, $rating->picture, false, false, true);
        echo '</td>';
        echo '<td class="author"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$rating->id.'&amp;course='.$df->data->course.'">'.fullname($rating).'</a></td>';
        echo '<td style="white-space:nowrap" align="center" class="rating">'.$scalemenu[$rating->rating].'</td>';
        echo "</tr>\n";
    }
    echo "</table>";
    echo "<br />";
}

close_window_button();
print_footer('none');
?>
