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
  $line_limit = intval($argv[1]);
  echo $line_limit;
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
        if (isset($line_limit) && $k > $line_limit) {
          echo "Line limit (" . $line_limit . ") reached";
          continue;
        } else {
          //Create an associative array for each row that includes the headers.
          //This makes it easier to get the values we want later
          foreach ($row1 as $key=>$value) { 
            $this_row_to_array[$value] = utf8_encode($data[(int)$key]);
          }
          //Add a new value to the line and write it to the file
          $administrative_data = get_administrative_data_from_postgis($this_row_to_array["Latitude"],$this_row_to_array["Longitude"]);
          $this_row_to_array["ward"] = $administrative_data["ward"];
          //add more lines here if you wish...
          
          fputcsv($fp, $this_row_to_array);
        }
      }
      fclose($fp); //close new file
      fclose($handle); //close old file
}

/**
 * Takes a lat/lng value, performs a lookup against a PostGIS database with boundary data in it 
 * and returns the administartive data
 * 
 * Currently just returns the electoral ward
 * 
 * Boundary data is taken from the Ordnance Survey Boundary Data
 * http://www.ordnancesurvey.co.uk/oswebsite/products/boundary-line/
 * 
 * @param float $lat 
 * @param float $lng
 * @return array $administrative_data Array of administrative data pertaining to the lat/lng that was looked up.
*/
function get_administrative_data_from_postgis($lat,$lng) {
  
  //Thanks: http://www.techrepublic.com/blog/howdoi/how-do-i-use-php-with-postgresql/110
  // attempt a connection
  //Note this is for my local test environment
  $dbh = pg_connect("host=localhost dbname=manchester user=david");
  if (!$dbh) {
     die("Error in connection: " . pg_last_error());
  }       

  // execute query
  //We have database with imported boundary file data from the Ordnance Survey in the UK
  //We use st_contains to check to see if a point is within a boundary
  //The decimal lat lng need SRID of 4326
  //The Ordnance Survey data needs 27700 hence the transform
  $sql = "  select boundary.name FROM boundary WHERE st_contains(
      boundary.the_geom,
        st_transform(st_setsrid(
          st_makepoint(" . $lng . "," . $lat ."),
          4326),27700));";
   $result = pg_query($dbh, $sql);
   if (!$result) {
       die("Error in SQL query: " . pg_last_error());
   }       

   // iterate over result set
   // print each row
   while ($row = pg_fetch_array($result)) {
       $ward = $row["name"];
   }       

   // free memory
   pg_free_result($result);       

   // close connection
   pg_close($dbh);
   
   if ($ward !=NULL) {
        $administrative_data["ward"] = $ward;
      }
  
   return $administrative_data;
}
?>
