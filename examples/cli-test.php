<?php

/**
 * Updated cli-test example
 *
 * @author Dan Paz-Horta, Jeremy Jones
 */

use GuzzleHttp\Exception\GuzzleException;
use Uicosss\AITS\PersonLookupConfidential;

try {

    require_once __DIR__ . '/../vendor/autoload.php';

    print_r($argv);
    echo PHP_EOL;

    // NetId
    if(empty($argv[3])){
        throw new Exception("Error: Specify NetId or UIN as the 3rd argument.");
    }

    // Subscription Key from Azure Gateway API
    if(empty($argv[1])){
        throw new Exception("Error: Specify API URL as the 1st argument.");
    }

    // API URL
    if(empty($argv[2])){
        throw new Exception("Error: Specify Subscription Key from AITS Azure API as the 2nd argument.");
    }

    // Call the AITS Person Lookup Confidential
    $personAPI = new PersonLookupConfidential($argv[1], $argv[2]);

    // Get the results of a call
    $personAPI->findPerson($argv[3]);

    echo "HTTP Code: [" . $personAPI->getHttpResponseCode() . "]" . PHP_EOL;

    echo "UIN: [" . $personAPI->getUin() . "]" . PHP_EOL;
    echo "First Name: [" . $personAPI->getFirstName() . "]" . PHP_EOL;
    echo "Last Name: [" . $personAPI->getLastName() . "]" . PHP_EOL;
    echo "NetID: [" . $personAPI->getNetID() . "]" . PHP_EOL;
    echo "Domain: [" . $personAPI->getDomain() . "]" . PHP_EOL;
    echo "NetID List:" . PHP_EOL;
    foreach ($personAPI->getAllNetIDs() as $n) {
        echo " - " . $n['netId'] . " campus: " . $n['campusDomain'] . PHP_EOL;
    }
    echo "Campus Domains List:" . PHP_EOL;
    foreach ($personAPI->getAllDomains() as $d) {
        echo " - " . $d . PHP_EOL;
    }
    echo "Email: [" . $personAPI->getEmail() . "]" . PHP_EOL;
    echo "Has FERPA data?: [" . (($personAPI->hasFerpaData()) ? 'Yes' : 'No') . "]" . PHP_EOL;
    echo "Is employee?: [" . (($personAPI->isEmployee()) ? 'Yes' : 'No') . "]" . PHP_EOL;
    echo "Employee title: [" . (($personAPI->isEmployee()) ? $personAPI->getTitle() : '') . "]" . PHP_EOL;

    echo PHP_EOL;

    // Get the raw response
    echo $personAPI->getResponse(true) . PHP_EOL;

    echo PHP_EOL;

} catch (GuzzleException|Exception $e) {

    print_r($e->getMessage());
    echo PHP_EOL;
    echo PHP_EOL;

}