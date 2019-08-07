<?php

/* 
 * SOGC-Scraper 
 * Jimmy Cappaert <jimmy@cappaert.com>
*/

// Variables

$params = array(	"limit"		=> 1000,
			"startdate"	=> date("Y-m-d", strtotime("6 days ago")),
			"enddate"	=> date("Y-m-d", strtotime("today")));

// Output header

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=sogc_results_{$params['limit']}_{$params['startdate']}_{$params['enddate']}.csv");

echo "UniqueID;CreationDate;Language;CompanyName;Street;City\n";

// Get companies

$curl = curl_init();
curl_setopt_array($curl, 
	[
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_TIMEOUT => 10,
		CURLOPT_URL => "https://www.shab.ch/api/v1/publications?allowRubricSelection=true&includeContent=false&pageRequest.page=0&pageRequest.size={$params['limit']}&publicationDate.end={$params['enddate']}&publicationDate.start={$params['startdate']}&publicationStates=PUBLISHED&publicationStates=CANCELLED&searchPeriod=LAST7DAYS&subRubrics=HR01"
	]
);

$result = curl_exec($curl);
curl_close($curl);

// Parse companies

$companies = json_decode($result);

foreach($companies->content as $company) {

	$company_id = $company->meta->id;
	$company_creationdate = date("Y-m-d", strtotime($company->meta->creationDate));
	$company_language = $company->meta->language;
	$company_name_raw = $company->meta->title->en;
	$company_name_raw = preg_replace("/New entries /", "", $company_name_raw);
	$company_name_raw = explode(",", $company_name_raw);
	$company_name = trim($company_name_raw[0]);

	// Get company details

	$curl = curl_init();
	curl_setopt_array($curl,
        	[
                	CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT => 10,
                	CURLOPT_URL => "https://www.shab.ch/api/v1/publications/$company_id/view"
        	]
	);

	$result = curl_exec($curl);
	curl_close($curl);

	// Parse company details

	$details = json_decode($result);

	$company_address_raw = $details->fields[0]->value->defaultValue;
	$company_address_raw = preg_replace("/(\<div\>|\<\/div\>)/i", "", $company_address_raw);
	$company_address_raw = preg_replace("/\<br \/\>\<br \/\>/i", "<br />", $company_address_raw);
	$company_address_raw = explode("<br />", $company_address_raw);

	// Output details
	
	echo "$company_id;$company_creationdate;$company_language;$company_name";

	foreach($company_address_raw as $element) {

		$val1 = trim(preg_quote($company_name, "/"));

		if(	!($company_name == $element) &&
			!preg_match("/$val1/i", $element) &&
			!preg_match("/\[(.*)\] (.*)/i", preg_quote($element, "/"))
			
		) {

			echo ";$element";

		}
		
	}		

	echo "\n";

}

exit;

?>
