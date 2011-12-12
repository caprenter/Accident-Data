<?php
/*
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 3 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Parse a csv file with geographic information and fetch, and cache, administrative data from http://www.uk-postcodes.com/
 *
 * This was built to get the administrative ward data for data about accidents in Manchester,UK
 * 
 * @package Accident_Data
 * @author David Carpenter caprenter@gmail.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html Freely available under GPLv3
 */
if (($handle = fopen("accidents.csv", "r")) !== FALSE) {
      $k=0;
      $row1 = fgetcsv($handle, 0, ',','"'); // read and store the first line
      while (($data = fgetcsv($handle, 1000, ',','"')) !== FALSE) {
        $k++;
        //Create an associative array for each row that includes the headers.
        //This makes it easier to get the values we want later
        foreach ($row1 as $key=>$value) { 
          $this_row_to_array[$value] = utf8_encode($data[(int)$key]);
        }
          $accident_data[] = $this_row_to_array;
      }
      fclose($handle);
  }
  
echo $k ." rows in the csv";


//Either fetch data or do some basic reporting on the existing data
if ($argv[1] == "nofetch") {
  //show...
  $wards = show_ward($accident_data);  
  $count = array_count_values($wards);  //count of wards
  $no_wards = count($count);            // number of wards
  $total = array_sum($count);           //number of records checked
  print_r($count);
  echo $total . " ward records processed" . PHP_EOL;
  echo $no_wards . " wards" . PHP_EOL;
  //die;
  //print_r(array_unique($wards));
} else {
  get_data($accident_data);
}


/*
 * Fetches data from the webservice based on lat/lng information from the csv file.
 * 
 * All data is saved to file. Repeat requests to the webservice are avoided.
 * 
 * name: get_data
 * @package Accident_Data
 * @param array $accident_data Built from a csv file, a one dimensional array of key value pairs containing Lat/Lng info
 * 
 */
function get_data($accident_data) {
  $lat_lng = array(); //store the lat/lngs as we go along
  $i=0;
  $j=0;

  foreach ($accident_data as $accident) {
    $i++;
    $lat =  $accident["Latitude"];//die;
    $lng = $accident["Longitude"];
    $lat = round($lat,2);
    $lng = round($lng,2);
    //echo $lat.$lng; die;
    $file = "data/" . $lat . "_" . $lng . ".json";
    if (in_array($lat.$lng, $lat_lng) || file_exists($file)) {
      //we've already done this lookup
    } else {
      //do a look up
      //ernest_marples($lat,$lng);
      //curl the data
      $url = "http://www.uk-postcodes.com/latlng/" . $lat . "," . $lng . ".json";
      $ch = curl_init($url);
      $fp = fopen($file, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        curl_close($ch);
      fclose($fp);
            
      $lat_lng[] = $lat.$lng;
      $j++;
      $lat_lng[] = $lat.$lng;
    }
    echo $i . " records searched" . PHP_EOL;
    echo $j . " files fetched" . PHP_EOL; //die;
    echo $file . PHP_EOL;
  }
//echo $i . " records searched" . PHP_EOL;
//echo $j . " files fetched" . PHP_EOL; //die;

}

/**
 * Performs a look up against stored data of administrative (ward) information based on a lat/lng co-ordinate.
 * 
 * name: show_ward
 * @param array $accident_data Built from a csv file a one dimensional array of key value pairs containing Lat/Lng info
 * @return array $wards An array or ward values. Will probably contain many repeated values.
 */
function show_ward($accident_data) {

  foreach ($accident_data as $accident) {
    $lat =  $accident["Latitude"];//die;
    $lng = $accident["Longitude"];
    $lat = round($lat,2);
    $lng = round($lng,2);
    $file = "data/" . $lat . "_" . $lng . ".json";
    //echo $file;
    $json = file_get_contents($file);
    $data = json_decode($json);
    $ward = $data->administrative->ward->title;
    if ($ward !=NULL) {
      $wards[] = $ward;
    } else {
      $errors[] = $file;
    }
  }

  return $wards;
}
?>
