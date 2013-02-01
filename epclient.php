<?php

class EpClient {

    public $id;
    public $fileds;
    public $key;
    public $title;
    public $creators_name;
    public $date;
    public $abstract;
    public $url_file;
    public $type;
    public $data_range = "1980-";
    public $order = "-date";

    private $ePrintsHost = 'http://alto2.vl-software.com';

    public function search() {
        $client = new SoapClient($this->ePrintsHost."/wsdl/SearchServ2.wsdl", array("trace" => 1, "exception" => 0, 'cache_wsdl' => WSDL_CACHE_NONE));
//        $client = new SoapClient("SearchServ2.wsdl", array("trace" => 1, "exception" => 0, 'cache_wsdl' => WSDL_CACHE_NONE));

        if (isset($this->data_range)) {
            $result = $client->searchEprint($this->key, $this->fileds, $this->data_range, $this->order);
        } else {
            $result = $client->searchEprint($this->key, $this->fileds, $this->order);
        }
        //debug
        //var_dump($client->__getLastRequest());
        return $result;
    }



    public function getMetadata() {
        $client = new SoapClient($this->ePrintsHost."/wsdl/MetaDataServ2.wsdl", array("trace" => 1, "exception" => 0, 'cache_wsdl' => WSDL_CACHE_NONE));


        $ObjectXML = '<listId>';
        foreach ($this->id as $id) {
            $ObjectXML.='<item>' . $id . '</item>';
        }
        $ObjectXML .= '</listId>';
        $ItemObject = new SoapVar($ObjectXML, XSD_ANYXML);

        $result = $client->getEprint($ItemObject);
        //var_dump($client->__getLastRequest());
        return $result;
    }



    public function put() {

        $ObjectXML = '<creators_name>';
        foreach ($this->creators_name as $creators) {
            $ObjectXML.='<item>
                        <given xsi:type="xsd:string">' . $creators['given'] . '</given>
                <family xsi:type="xsd:string">' . $creators['family'] . '</family>
            </item>';
        }
        $ObjectXML.='</creators_name>';

        $ItemObject = new SoapVar($ObjectXML, XSD_ANYXML);
        $client = new SoapClient($this->ePrintsHost."/wsdl/putEprints2_string.wsdl", array("trace" => 1, "exception" => 0, 'cache_wsdl' => WSDL_CACHE_NONE));
        //$client = new SoapClient("putEprints2_string.wsdl", array("trace" => 1, "exception" => 0, 'cache_wsdl' => WSDL_CACHE_NONE));
        $result = $client->putEprint($this->title, $ItemObject, $this->date, $this->abstract, $this->url_file, $this->type);
        //var_dump($client->__getLastRequest());
        return $result;
    }




}

/*
$search = new EpClient();

//  search data, return list id

$search->fileds = array('fileds' => 'title');
$search->key = 'test';
//data range in form yyyy- or -yyyy or yyyy-zzzz
//$search->data_range = '2009-2010';
$result_search = $search->search();



$search->fileds = array('fileds' => 'title');
$search->key = 'and';
$result_search1 = $search->search();

//search by author
$search2->fileds = array('fileds' => 'author');
$search2->creators_name = array(array('family'=>'Gajos'));
$result_search2 = $search->search();

//search by year
$search3->fileds = array('fileds' => 'date');
$search3->data_range = '2012-';
$result_search3 = $search->search();

var_dump($result_search, $result_search1, $result_search2, $result_search3);

*/



/*

$search = new EpClient();

//  search data, return list id

$search->fileds = array('fileds' => 'title');
$search->key = 'test';
//data range in form yyyy- or -yyyy or yyyy-zzzz
//$search->data_range = '2009-2010';
$result_search = $search->search();


$search1 = new EpClient();
$search1->fileds = array('fileds' => 'title');
$search1->key = 'and';
$result_search1 = $search1->search();

//search by author
$search2 = new EpClient();
$search2->fileds = array('fileds' => 'author');
$search2->creators_name = array(array('family'=>'Gajos'));
$result_search2 = $search2->search();

//search by year
$search3 = new EpClient();
$search3->fileds = array('fileds' => 'date');
$search3->data_range = '2012-';
$result_search3 = $search3->search();

//var_dump($result_search, $result_search1, $result_search2, $result_search3);


// get medatada by id item, return list metadata

//
$search->id = $result_search; //array(115,116,118);
$result_metadata = $search->getMetadata();



// put metadata

//$search->title = 'Test soap client php';
//$search->abstract = 'testing soap client php';
//$search->creators_name = array(array('family' => 'test family1', 'given' =>'test given1'), array('family' => 'test family2', 'given' =>'test given2'));
//$search->date = '2012-09-30';
//$search->type = 'article';
//$search->url_file = '/var/moodledata233/filedir/01/a5/01a59d0e6774ef7b83dc18da2d97db535f252f46';
//$result_put = $search->put();

//var_dump($result_search);
//var_dump($result_metadata);
//var_dump($result_put);
*/

?>
