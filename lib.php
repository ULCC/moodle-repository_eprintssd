<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin is used to access youtube videos
 *
 * @since 2.0
 * @package    repository_eprintssd
 * @copyright  2010 Dongsheng Cai {@link http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * repository_eprintssd class
 *
 * @since 2.0
 * @package    repository_eprintssd
 * @copyright  2009 Dongsheng Cai {@link http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class repository_eprintssd extends repository {
    /** @var int maximum number of thumbs per page */
    const TITLES_PER_PAGE = 10;

    /**
     * eprintssd plugin constructor
     * @param int $repositoryid
     * @param object $context
     * @param array $options
     */
  public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
    }

    public function check_login() {
        return !empty($this->keyword);
    }

    /**
     * Return search results
     * @param string $search_text
     * @return array
     */
    public function search($search_text, $page = 0) {
        global $SESSION;
        $sort = optional_param('eprintssd_sort', '', PARAM_TEXT);
        $sess_keyword = 'eprintssd_'.$this->id.'_keyword';
        $sess_sort = 'eprintssd_'.$this->id.'_sort';

        // This is the request of another page for the last search, retrieve the cached keyword and sort
        if ($page && !$search_text && isset($SESSION->{$sess_keyword})) {
            $search_text = $SESSION->{$sess_keyword};
        }
        if ($page && !$sort && isset($SESSION->{$sess_sort})) {
            $sort = $SESSION->{$sess_sort};
        }
        if (!$sort) {
            $sort = 'relevance'; // default
        }

        // Save this search in session
        $SESSION->{$sess_keyword} = $search_text;
        $SESSION->{$sess_sort} = $sort;

        $this->keyword = $search_text;
        $ret  = array();
        $ret['nologin'] = true;
        $ret['page'] = (int)$page;
        if ($ret['page'] < 1) {
            $ret['page'] = 1;
        }
        $start = ($ret['page'] - 1) * self::TITLES_PER_PAGE + 1;
        $max = self::TITLES_PER_PAGE;
        $ret['list'] = $this->_get_collection($search_text, $start, $max, $sort);
        $ret['norefresh'] = true;
        $ret['nosearch'] = true;
        $ret['pages'] = -1;
        return $ret;
    }

    /**
     * Private method to get eprintssd search results
     * @param string $keyword
     * @param int $start
     * @param int $max max results
     * @param string $sort
     * @return array
     */
    private function _get_collection($keyword, $start, $max, $sort) {
        $list = array();
        $this->feed_url = 'http://gdata.youtube.com/feeds/api/videos?q=' . urlencode($keyword) . '&format=5&start-index=' . $start . '&max-results=' .$max . '&orderby=' . $sort;
        $c = new curl(array('cache'=>true, 'module_cache'=>'repository'));
        $content = $c->get($this->feed_url);
        $xml = simplexml_load_string($content);
        $media = $xml->entry->children('http://search.yahoo.com/mrss/');
        $links = $xml->children('http://www.w3.org/2005/Atom');
        foreach ($xml->entry as $entry) {
            $media = $entry->children('http://search.yahoo.com/mrss/');
            $title = (string)$media->group->title;
            $description = (string)$media->group->description;
            if (empty($description)) {
                $description = $title;
            }
            $attrs = $media->group->thumbnail[2]->attributes();
            $thumbnail = $attrs['url'];
            $arr = explode('/', $entry->id);
            $id = $arr[count($arr)-1];
            $source = 'http://www.youtube.com/v/' . $id . '#' . $title;
            $list[] = array(
                'shorttitle'=>$title,
                'thumbnail_title'=>$description,
                'title'=>$title.'.avi', // this is a hack so we accept this file by extension
                'thumbnail'=>(string)$attrs['url'],
                'thumbnail_width'=>(int)$attrs['width'],
                'thumbnail_height'=>(int)$attrs['height'],
                'size'=>'',
                'date'=>'',
                'source'=>$source
            );
        }
        return $list;
    }

    /**
     * eprintssd plugin doesn't support global search
     */
    public function global_search() {
        return false;
    }

    public function get_listing($path='', $page = '') {
        return array();
    }

    /**
     * Generate search form
     */
    public function print_login($ajax = true) {
        $ret = array();
        $search = new stdClass();
        $search->type = 'text';
        $search->id   = 'eprintssd_search';
        $search->name = 's';
        $search->label = get_string('search', 'repository_eprintssd').': ';
        $sort = new stdClass();
        $sort->type = 'select';
        $sort->options = array(
            (object)array(
                'value' => 'date',
                'label' => get_string('byyearoldestfirst', 'repository_eprintssd')
            ),
            (object)array(
                'value' => '-date',
                'label' => get_string('byyearmostrecentfirst', 'repository_eprintssd')
            ),
            (object)array(
                'value' => 'creators_name',
                'label' => get_string('byauthorsname', 'repository_eprintssd')
            ),
            (object)array(
                'value' => 'title',
                'label' => get_string('bytitle', 'repository_eprintssd')
            )
        );
        $sort->id = 'eprintssd_sort';
        $sort->name = 'eprintssd_sort';
        $sort->label = get_string('sortby', 'repository_eprintssd').': ';
        $ret['login'] = array($search, $sort);
        $ret['login_btn_label'] = get_string('search');
        $ret['login_btn_action'] = 'search';
        $ret['allowcaching'] = true; // indicates that login form can be cached in filepicker.js
        return $ret;
    }

    /**
     * file types supported by eprintssd plugin
     * @return array
     */
    public function supported_filetypes() {
        return array('*.*');
    }

    /**
     * eprintssd plugin only return external links
     * @return int
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL;
    }
}
