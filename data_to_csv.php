<?php
/*
 *      This program is free software: you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation, either version 3 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *      
 */

/**
 * This file takes a csv file and creates a new version with additonal data added 
 *
 * If an integer value is supplied at run time, the script will process that number of records.
 * e.g. php data_to_csv.php 10 
 * will process 10 records
 * If no arguments are supplied the whole file will be processed
 * 
 * @package Accident_Data
 * @author David Carpenter caprenter@gmail.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html Freely available under GPLv3
 */

$old_csv = "accidents.csv";
$new_csv = "amened_accidents.csv";

//Specify number of records to be processed at the command line if you like
if (isset($argv[1]) && $argv[1] != NULL) {
  $line_limit = $argv[1];
}

if (($handle = fopen($old_csv, "r")) !== FALSE) {
    
      $fp = fopen($new_csv, "w");                 //open new file for writing
      $k=0;                                       //counter for processing limited records
      $row1 = fgetcsv($handle, 0, ',','"');       // read and store the first line
      
      //Create new column headers for the new csv
      $new_row1 = $row1;
      $new_columns =array("Ward"); //add your new columns here
      foreach ($new_columns as $column) {
        array_push($new_row1,$column);
      }
      fputcsv($fp, $new_row1); // write column headers with first line in new csv
      
      //Loop through the data adding new data to each line and saveit to the new csv
      while (($data = fgetcsv($handle, 1000, ',','"')) !== FALSE) {
        $k++;
        //If a line limit is set anf we are above it, then just skip through
        if (isset($line_limit) && is_int($line_limit) && $k > $line_limit) {
          echo "yes";
          continue;
        } else {
          //Create an associative array for each row that includes the headers.
          //This makes it easier to get the values we want later
          foreach ($row1 as $key=>$value) { 
            $this_row_to_array[$value] = utf8_encode($data[(int)$key]);
          }
          //Add a new value to the line and write it to the file
          $administrative_data = get_administrative_data($this_row_to_array["Latitude"],$this_row_to_array["Longitude"]);
          $this_row_to_array["ward"] = $administrative_data["ward"];
          //add more lines here if you wish...
          
          fputcsv($fp, $this_row_to_array);
        }
      }
      fclose($fp); //close new file
      fclose($handle); //close old file
  }


/**
 * Takes a lat/lng value, looks for a file of that name, parses it and returns the administartive data
 * 
 * Currently just returns the electoral ward
 * 
 * Files are pre-cached from a call to http://www.uk-postcodes.com and saved with a useful 
 * filename that we can perform lookups against.
 * 
 * @param float $lat 
 * @param float $lng
 * @return array $administrative_data Array of administrative data pertaining to the lat/lng that was looked up.
*/
function get_administrative_data($lat,$lng) {

    $lat = round($lat,2);
    $lng = round($lng,2);
    $file = "data/" . $lat . "_" . $lng . ".json";
    //echo $file;
    $json = file_get_contents($file);
    $data = json_decode($json);
    $ward = $data->administrative->ward->title;
    if ($ward !=NULL) {
      $administrative_data["ward"] = $ward;
    }
    return $administrative_data;
}
?>
