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
 * @since 2.0
 * @package    repository_eprintssd
 * @copyright  2012 Greg Pasciak/ULCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/eprintssd/epclient.php');

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
        $this->_options['pluginname'] = 'eprintssd';
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
        global $CFG;
//-----------------------------------------gregp

        //$client = new SoapClient('http://'.$this->srwhost.':'.$this->srwport.$this->srwpath);

        $client = new EpClient();

        $client->fileds = array('fileds' => 'title');
        $client->key = $_POST['title'];
        $resultIds = $client->search();
        if (!empty($resultIds )){
            $searchMeta = new EpClient();
            $searchMeta->id=$resultIds;
            $records = $searchMeta->getMetadata();
            $results = array();
            if (!empty($records)) {

              foreach ($records->ResourcesList as $record) {
                $creators ='';
                if (is_array($record->CreatorsList->someArray->item)){
                    foreach ($record->CreatorsList->someArray->item as $item){
                        if (!empty($creators))  $creators.='; ';
                        $creators .= $item;
                    }
                }
                else {
                    $creators = $record->CreatorsList->someArray->item;
                }
                $results []= array(
                    'title'=>$record->title.' by '.$creators,
                    //'thumbnail'=>$record->abstract,
                    'shorttitle' => $record->title,
                    'date'=> time(),
                    'size' => 1,
                    'thumbnail'=> $CFG->wwwroot . '/repository/eprintssd/pix/repo.png',
                    //'thumbnail'=> 'http://vl-software.com/moodle/repository/eprintssd/pix/repo.png',
                    'thumbnail_width'=>80,
                    'thumbnail_height'=>36,
                    'author' => $creators,
                    //'date' => $record->date,
                    //'abstract' => $record->abstract,
                    'url' => $record->relation);
              }

            }
        }

        else {

            $results []= array(
                'title'=>'No results found, try to use other words...',
                //'thumbnail'=>$record->abstract,
                'shorttitle' => 'No results found, try to use other words...',
                'date'=> time(),
                'size' => 1,
                'thumbnail'=> $CFG->wwwroot . '/repository/eprintssd/pix/empty.png',
                //'thumbnail'=> 'http://vl-software.com/moodle/repository/eprintssd/pix/repo.png',
                'thumbnail_width'=>256,
                'thumbnail_height'=>128,
                'author' => '',
                //'date' => $record->date,
                //'abstract' => $record->abstract,
                'url' => '');

        }

        return $results;

//-----------------------------------------
    }


    /**
     * Download a file - originally to download a file, now for returning URL to repository
     *
     * @global object $CFG
     * @param string $url the url of file
     * @param string $filename save location
     * @return string the location of the file
     * @see curl package
     */
    public function get_file($url, $filename = '') {
        global $CFG;
        //$path = $this->prepare_file($filename);
        //$fp = fopen($path, 'w');
        //$c = new curl;
        //$c->download(array(array('url'=>$url, 'file'=>$fp)));
        return array('path'=>'', 'url'=>$url);
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
        $search1 = new stdClass();
        $search1->type = 'hidden';
        $search1->label = get_string('search', 'repository_eprintssd');

        $search2 = new stdClass();
        $search2->type = 'text';
        $search2->id   = 'title';
        $search2->name = 'title';
        $search2->label = get_string('bytitle', 'repository_eprintssd').': ';

        $search3 = new stdClass();
        $search3->type = 'text';
        $search3->id   = 'creators_name';
        $search3->name = 'creators_name';
        $search3->label = get_string('byauthorsname', 'repository_eprintssd').': ';

        $search4 = new stdClass();
        $search4->type = 'text';
        $search4->id   = 'date';
        $search4->name = 'date';
        $search4->label = get_string('byyearmostrecentfirst', 'repository_eprintssd').': ';

        $ret['login'] = array($search1, $search2, $search3, $search4);
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
        return array('*');
    }

    /**
     * eprintssd plugin only return external links
     * @return int
     */
    public function supported_returntypes() {
        return FILE_INTERNAL | FILE_EXTERNAL | FILE_REFERENCE;
    }
}
