<?php



/**
 * Define a function to sort two dimensional array by values
 */
function sort_2d_array_by_value($arr, $field, $sort){
  $sorted_a = array();
  foreach($arr as $k=>$v){
    // create an array to store the field values
    $field_array[$k] = $v[$field]; 
  }
  
  if($sort == 'asc'){
    asort($field_array);
  }
  else{
    arsort($field_array);
  }
  
  foreach(array_keys($field_array) as $k){
    $sorted_a[] = $arr[$k];
  }
  
  return $sorted_a;
}
//================End of sort_2d_array_by_value() =====================


/**
 * Define a function to get the primary key of a table
 */
function get_primary_key($table_name){
  if(in_array($table_name, get_chado_table_list())){
    $table = 'chado.'.$table_name;
    $primary_key_sql = 'SELECT a.attname
              FROM   pg_index i
              JOIN   pg_attribute a ON a.attrelid = i.indrelid
                               AND a.attnum = ANY(i.indkey)
              WHERE  i.indrelid = \''.$table.'\'::regclass
              AND    i.indisprimary;';

    $primary_key = db_query($primary_key_sql)->fetchAssoc();
  }
  else{
    $table = $table_name;
    $primary_key_sql = 'SELECT a.attname
              FROM   pg_index i
              JOIN   pg_attribute a ON a.attrelid = i.indrelid
                         AND a.attnum = ANY(i.indkey)
              WHERE  i.indrelid = \''.$table.'\'::regclass
              AND    i.indisprimary;';

    $primary_key = db_query($primary_key_sql)->fetchAssoc();
  }


  if(is_array($primary_key)){
    $primary_key = implode($primary_key);
  }

  return $primary_key;

}//============== End of primary key function ================================






/**
 * This function return an array containing tables from the chado schema
 */
function get_chado_table_list() {

    $sql_table_list = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'chado' ORDER BY table_name;";
    $result_table = db_query($sql_table_list);
    $input_table = $result_table->fetchAll();


    $table_list = array();
    $i = 0;
    foreach ($input_table as $value) {
        $table_list[$i] = $value->table_name;
        $i++;
    }

    return $table_list;
}


/**
 * This function takes a table name and return all the column names
 */
function get_column_list($table_name) {
    $sql_column_list = "SELECT column_name FROM information_schema.columns WHERE (table_schema = 'public'OR table_schema = 'chado') AND table_name = :selected_table;";
    $result_column = db_query($sql_column_list, array(':selected_table' => $table_name));
    $input_column = $result_column->fetchAll();
    $column_list = array();

    $k = 0;
    foreach($input_column as $value) {
        $column_list[$k] = $value->column_name;
        $k++;
    }

    return $column_list;
}

/**
 * This function return an array containing a list of table names from the public OR chado schema.
 * All the tables are from the chado schema or public schema.
 */
function get_table_list() {

    $sql_table_list = "SELECT table_name FROM information_schema.tables WHERE (table_schema = 'public' OR table_schema = 'chado') ORDER BY table_name;";
    $result_table = db_query($sql_table_list);
    $input_table = $result_table->fetchAll();


    $table_list = array('index_website');
    $i = 1;
    foreach ($input_table as $value) {
        $table_list[$i] = $value->table_name;
        $i++;
    }

    return $table_list;
}



/**
 * This function transforms an object to an array resursively.<br/>
 */
function objectToArray($d) {
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    }
    else {
        // Return array
        return $d;
    }
}

/**
 * This function transform a nested array to a flattened array.
 */
function flatten($arr, $prefix = '') {
        $out = array();
        foreach ($arr as $key => $value) {
                $key = (!strlen($prefix)) ? $key : "{$prefix}_$key";
                if (is_array($value)) {
                        $out += flatten($value, $key);
                } else {
                        $out[$key] = $value;
                }
        }

        return $out;
}



/** function to build elastic query for each field **/
function _build_elastic_query($searchMethod, $field, $keyword){

    switch($searchMethod){
        case 'query_string':
            $query_string = ' {"query_string":{"default_field":"_field_", "query":"_keyword_", "default_operator":"AND"}} ';
            $search = array("_field_", "_keyword_");
            $replace = array($field, $keyword);
            $final_query_string = str_replace($search, $replace, $query_string);
            break;
        case 'match':
            $query_string = ' {"match":{"_field_":"_keyword_"}} ';
            $search = array("_field_", "_keyword_");
            $replace = array($field, $keyword);
            $final_query_string = str_replace($search, $replace, $query_string);
            break;
        case 'fuzzy':
            $query_string = ' {"match":{"_field_": {"query":"_keyword_", "fuzziness":"AUTO", "operator":"AND" }}} ';
            $search = array("_field_", "_keyword_");
            $replace = array($field, $keyword);
            $final_query_string = str_replace($search, $replace, $query_string);
            break;
        case 'match_phrase':
            $query_string = ' {"match_phrase": {"_field_":"_keyword_"}} ';
            $search = array("_field_", "_keyword_");
            $replace = array($field, $keyword);
            $final_query_string = str_replace($search, $replace, $query_string);
            break;
        case 'sort_ascending':
            $query_string = ' {"_field_":{"order":"asc"}} ';
            $search = array("_field_", "_keyword_");
            $replace = array($field, $keyword);
            $final_query_string = str_replace($search, $replace, $query_string);
            break;
        case 'sort_descending':
            $query_string = ' {"_field_":{"order":"desc"}} ';
            $search = array("_field_", "_keyword_");
            $replace = array($field, $keyword);
            $final_query_string = str_replace($search, $replace, $query_string);
            break;
        case 'range':
            break;
    }

    return $final_query_string;
}




/* 
 * Build elatic search queries
 */
function _build_elastic_search_query($field, $keyword, $searchMethod='query_string'){
  $query_string_template = ' {"'.$searchMethod.'":{"default_field":"_field_", "query":"_keyword_", "default_operator":"AND"}} ';
  $search = array("_field_", "_keyword_");
  $replace = array($field, $keyword);
  $query = str_replace($search, $replace, $query_string_template);

  return $query;
}



/*
 * Escape special characters for elasticsearch
 */
function _remove_special_chars($keyword){
  $elastic_special_chars = array('+', '-', '=', '&&', '||', '>',
                                 '<', '!', '(', ')', '{', '}', '[',
                                 ']', '^', '"', '~', '*', '?', ':', '\\', '/');

  $keyword = trim($keyword);
  // Check if $keyword starts and ends with double quotations
  $start = substr($keyword, 0, 1);
  $end = substr($keyword, -1, 1);
  $keyword = str_replace($elastic_special_chars, ' ', $keyword);
  if($start == '"' and $end == '"'){
    $keyword = '\"'.$keyword.'\"';
  }

  return $keyword;
}



/*
 * This function takes form input and return an array, of which
 * the keys are field names and values are corresponding keywords.
 */
function _get_field_keyword_pairs($form_input){
  $table = array_keys($form_input)[0];
  $field_keyword_pairs = $form_input[$table];
  return array('table'=>$table, 'field_keyword_pairs'=>$field_keyword_pairs);
}


function _run_elastic_search($table, $field_keyword_pairs, $from=0, $size=1000){

  $body_curl_head = '{';
  $body_boolean_head = '"query" : {"bool" : {"must" : [';
  $body_boolean_end = ']}}';
  $body_curl_end = '}';

  $body_query_elements = array();
  foreach($field_keyword_pairs as $field=>$keyword){
    //Put queries in an array
    if(!empty($keyword)){
      $keyword = _remove_special_chars($keyword);
      $body_query_elements[] = _build_elastic_search_query($field, $keyword);
    }
  }
  $body_query = implode(',', $body_query_elements);
  $body = $body_curl_head.$body_boolean_head.$body_query.$body_boolean_end.$body_curl_end;

  $client = new Elasticsearch\Client();
  $params = array();
  $params['index'] = $table;
  $params['type'] = $table;
  $params['body'] = $body;
  $search_hits_count = $client->count($params)['count'];

  $params['from'] = $from;
  $params['size'] = $size;
  $search_results = $client->search($params);

  $search_hits= array();
  foreach($search_results['hits']['hits'] as $key=>$value){
      foreach($field_keyword_pairs as $field=>$keyword){
        $search_hits[$key][$field] = $value['_source'][$field];
      }
  }

  return array('search_hits_count'=>$search_hits_count, 'search_hits'=>$search_hits, 'search_results'=>$search_results);

}




/*
 * This function takes in the search_hits array and 
 * return a themed table.
 */
function get_search_hits_table($search_hits){
  // Get table header
  $elements = array_chunk($search_hits, 1);
  //dpm($elements);
  $header = array();
  foreach($elements[0] as $value){
    foreach(array_keys($value) as $field){
      $header[] = array('data'=>$field, 'field'=>$field);
    }
  }

  if(isset($_GET['sort']) and isset($_GET['order'])){
    $sorted_hits_records = sort_2d_array_by_value($search_hits, $_GET['order'], $_GET['sort']);
  }
  else{
    // By default, the table is sorted by the first column by ascending order.
    $sorted_hits_records = sort_2d_array_by_value($search_hits, $header[0]['field'], 'asc');
  }

  //Get table rows
  foreach($sorted_hits_records as $values){
    $rows[] = array_values($values);
  }

  $per_page = 10;
  $current_page = pager_default_initialize(count($rows), $per_page);
  $chunks = array_chunk($rows, $per_page, TRUE);
  $output = theme('table', array('header' => $header, 'rows' => $chunks[$current_page] ));
  $output .= theme('pager', array('quantity', count($rows)));

  return $output;
}



/*
 * Test if a string is an elasticsearch index
 */
function is_elastic_index($index){
  $client = new Elasticsearch\Client();
  $mappings = $client->indices()->getMapping();
  $indices = array_keys($mappings);
  $res = false;
  if(in_array($index, $indices)){
    $res = true;
  }

  return $res;
}
