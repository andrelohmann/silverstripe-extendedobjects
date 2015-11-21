<?php

/**
 * This will add the geodistance function to your mysql database
 */
class AddGeodistanceFunction extends DataExtension{
    
    // Set Default Frontend group for new members
    public function requireDefaultRecords() {
        parent::requireDefaultRecords();
        
        if(defined('CREATE_GEODISTANCE_UDF') && GEOFORM_CREATE_GEODISTANCE_UDF){
            if(!defined('CreateGeodistanceOnce')){
                define('CreateGeodistanceOnce', true);

                $q1 = "DROP FUNCTION IF EXISTS geodistance;";
                $q2 = "
CREATE FUNCTION `geodistance`(lat1 DOUBLE, lng1 DOUBLE, lat2 DOUBLE, lng2 DOUBLE) RETURNS double
    NO SQL
BEGIN
DECLARE radius DOUBLE;
DECLARE distance DOUBLE;
DECLARE vara DOUBLE;
DECLARE varb DOUBLE;
DECLARE varc DOUBLE;
SET lat1 = RADIANS(lat1);
SET lng1 = RADIANS(lng1);
SET lat2 = RADIANS(lat2);
SET lng2 = RADIANS(lng2);
SET radius = 6371.0;
SET varb = SIN((lat2 - lat1) / 2.0);
SET varc = SIN((lng2 - lng1) / 2.0);
SET vara = SQRT((varb * varb) + (COS(lat1) * COS(lat2) * (varc * varc)));
SET distance = radius * (2.0 * ASIN(CASE WHEN 1.0 < vara THEN 1.0 ELSE vara END));
RETURN distance;
END
";            
                DB::query($q1);
                DB::query($q2);

                DB::alteration_message('MySQL geodistance function created', 'created');
            }
        }
    }
}