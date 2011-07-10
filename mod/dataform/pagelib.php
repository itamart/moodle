<?php // $Id$

require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->dirroot.'/course/lib.php'); // needed for some blocks

define('PAGE_DATAFORM_VIEW',   'mod-dataform-view');

page_map_class(PAGE_DATAFORM_VIEW, 'page_dataform');

$DEFINEDPAGES = array(PAGE_DATAFORM_VIEW);
/*
*/

/**
 * Class that models the behavior of a dataform
 *
 * @author Jon Papaioannou
 * @package pages
 */

class page_dataform extends page_generic_activity {

    function init_quick($data) {
        if(empty($data->pageid)) {
            error('Cannot quickly initialize page: empty course id');
        }
        $this->activityname = 'dataform';
        parent::init_quick($data);
    }

    function print_header($title, $morenavlinks = NULL, $meta) {
        parent::print_header($title, $morenavlinks, '', $meta);
    }

    function get_type() {
        return PAGE_DATAFORM_VIEW;
    }
}

?>