# University of Illinois
## AITS - Person Lookup Confidential

PHP library for using the AITS Person Lookup Confidential API. Contact AITS for additional implementation details.

## Usage
To use the library, you need to:

### Include library in your program
`require_once 'AitsPersonLookupConfidential.php';`

### or use composer 
```
composer require uicosss/aits_person_lookup_confidential
require_once 'vendor/autoload.php';
```

### Instantiate an object of the class
```
$apiUrl = 'apiurl.com/without/trailing/slash'; // Contact AITS for this
$subscriptionKey = 'YOUR_SUBSCRIPTION_KEY'; // Contact AITS for this
$personApi = new uicosss\AitsPersonLookupConfidential($apiUrl, $subscriptionKey);
```

### Getting Results from an API call
The default response will be JSON, but you can also request the raw data which will be an object of StdClass. Contact AITS for additional details on API schema.
```
$lookupKey = 'sparky'; // NetID or UIN
$personApi->findPerson($lookupKey); // Conduct the person lookup
echo $personApi->getResponse(true); // See raw JSON response
$json = $personApi->getResponse(); // Get decoded JSON array
```

## Examples:
You can use the attached `examples/cli-test.php` file from the command line to test functionality.

`php cli-test.php apiurl.com/without/trailing/slash YOUR_SUBSCRIPTION_KEY lookupkey`