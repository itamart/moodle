<?php  //$Id$

// enable rss feeds
if (empty($CFG->enablerssfeeds)) {
    $options = array(0 => get_string('rssglobaldisabled', 'admin'));
    $str = get_string('configenablerssfeeds', 'dataform').'<br />'.get_string('configenablerssfeedsdisabled2', 'admin');

} else {
    $options = array(0=>get_string('no'), 1=>get_string('yes'));
    $str = get_string('configenablerssfeeds', 'dataform');
}
$settings->add(new admin_setting_configselect('dataform_enablerssfeeds', get_string('enablerssfeeds', 'admin'),
                   $str, 0, $options));

$unlimited = get_string('unlimited');
$keys = range(0,500);
$values = range(1,500);
array_unshift($values, $unlimited);
                   
// max fields
$options = array_combine($keys, $values);
$settings->add(new admin_setting_configselect('dataform_maxfields', get_string('fieldsmax', 'dataform'),
                   get_string('configmaxfields', 'dataform'), $unlimited, $options));

// max views
$options = array_combine($keys, $values);
$settings->add(new admin_setting_configselect('dataform_maxviews', get_string('viewsmax', 'dataform'),
                   get_string('configmaxviews', 'dataform'), $unlimited, $options));

// max filters
$options = array_combine($keys, $values);
$settings->add(new admin_setting_configselect('dataform_maxfilters', get_string('filtersmax', 'dataform'),
                   get_string('configmaxfilters', 'dataform'), $unlimited, $options));

// max entries
$options = array_combine($keys, $values);
$settings->add(new admin_setting_configselect('dataform_maxentries', get_string('entriesmax', 'dataform'),
                   get_string('configmaxentries', 'dataform'), $unlimited, $options));

?>