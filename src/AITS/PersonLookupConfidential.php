<?php

/**
 * University of Illinois - AITS Person Lookup Confidential
 * API Wrapper
 *
 * @author Jeremy Jones
 * @license MIT
 */

namespace Uicosss\AITS;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

class PersonLookupConfidential
{
    protected $apiUrl;
    protected $subscriptionKey;

    protected $uin = 0;

    protected $firstName = null;

    protected $lastName = null;

    protected $netIDs = [];

    protected $campusDomains = [];

    protected $ferpaSuppressed = false;

    protected $email = null;

    protected $title = null;

    protected $employee = [];

    protected $address = [];
    
    protected $json = [];
    
    protected $raw = '';

    protected $httpCode = 500;

    /**
     * Sets the two necessary variables for the AITS API call to operate successfully
     *
     * @param string $apiUrl AITS API URL without leading "https:" or trailing "/"
     * @param string $subscriptionKey AITS Subscription Key pulled from the necessary profile
     * @throws Exception
     */
    public function __construct(string $apiUrl, string $subscriptionKey)
    {
        $this->setApiUrl($apiUrl);

        $this->setSubscriptionKey($subscriptionKey);
    }

    /**
     * Executes an AITS API call to find the given parameter.
     * Will throw exception on error, otherwise it will simply
     * assign the API response values to the object variables.
     *
     * @param string $lookupKey NetID or UIN lookup value
     * @throws Exception|GuzzleException
     */
    public function findPerson(string $lookupKey): void
    {
        $lookupKey = $this->checkLookupKey($lookupKey);

        $client = new Client();

        $request = new Request('GET', $this->apiUrl . $lookupKey, [
            'Cache-Control' => 'no-cache',
            'Ocp-Apim-Subscription-Key' => $this->subscriptionKey
        ]);

        try {

            $response = $client->send($request);

            $this->httpCode = $response->getStatusCode();
            $this->raw = $response->getBody();
            $this->json = json_decode($response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('AITS API response was not valid JSON');
            }

            if (!isset($this->json['list'])) {
                throw new Exception('AITS API did not provide a proper response');
            }

            if (count($this->json['list'][0]) == 0) {
                throw new Exception('NetID or UIN not found');
            }

            // List should now be an array with a single (?) element that contains all the info needed
            // All data will currently be assigned to variables with the class object.

            // Default UIN to blank if not detected
            // todo: Should an empty UIN trigger an exception instead? Is that even possible?
            $this->uin = $this->json['list'][0]['uin'] ?? 0;

            // Just in case they are not present we can default to blank
            $this->firstName = $this->json['list'][0]['name']['firstName'] ?? null;
            $this->lastName = $this->json['list'][0]['name']['lastName'] ?? null;

            // Each element contains a "netId" and "campusDomain"
            foreach ($this->json['list'][0]['netIds'] as $n) {
                // Keeping the link between netId and campusDomain
                $this->netIDs[] = $n;

                // While also keeping a unique list of campusDomains
                if (!empty($n['campusDomain']) && !in_array($n['campusDomain'], $this->campusDomains)) {
                    $this->campusDomains[] = $n['campusDomain'];
                }
            }

            // Whether the UIN has FERPA suppressed data
            if (!empty($this->json['list'][0]['confidentialInd']) && $this->json['list'][0]['confidentialInd'] == 'Y') {
                $this->ferpaSuppressed = true;
            }

            // Edge cases where the email is not present, this element would not exist in the response
            $this->email = $this->json['list'][0]['email']['emailAddress'] ?? null;

            // Title is not within the employee element, going to keep it the same here
            // todo: if title is present is that enough to say they are an employee?
            $this->title = $this->json['list'][0]['title'] ?? '';

            // Employees will have extra data present, may never use
            $this->employee = $this->json['list'][0]['employee'] ?? [];

            // Capture address field, may never use
            $this->address = $this->json['list'][0]['address'] ?? [];

        } catch (ClientException $ex) {
            $this->httpCode = $ex->getCode();
            $json = json_decode($ex->getResponse()->getBody(), true);
            throw new Exception(json_last_error() == JSON_ERROR_NONE ? $json['message'] : 'Error');
        } catch (ServerException|BadResponseException|Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function getUin(): int
    {
        return $this->uin;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getEmployee(): array
    {
        return $this->employee;
    }

    /**
     * @param int $k Optional key to pull a specific NetID
     * @return string
     */
    public function getNetID(int $k = 0): ?string
    {
        return $this->netIDs[$k]['netId'] ?? null;
    }

    public function getAllNetIDs(): array
    {
        return $this->netIDs;
    }

    /**
     * @param int $k Optional key to pull a specific NetID's Campus Domain
     * @return string
     */
    public function getDomain(int $k = 0): ?string
    {
        return $this->netIDs[$k]['campusDomain'] ?? null;
    }

    public function getAllDomains(): array
    {
        return $this->campusDomains;
    }

    public function isFerpaSuppressed(): bool
    {
        return $this->ferpaSuppressed;
    }

    public function isEmployee(): bool
    {
        return !empty($this->employee);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param bool $raw Boolean flag for whether to return raw JSON string or decoded JSON array
     * @return mixed Will return the JSON string or decoded JSON array
     */
    public function getResponse(bool $raw = false)
    {
        return ($raw) ? $this->raw : $this->json;
    }

    public function getHttpResponseCode()
    {
        return $this->httpCode;
    }

    /**
     * @param string $apiUrl AITS API URL with protocol, trailing slash optional
     * @throws Exception
     */
    private function setApiUrl(string $apiUrl)
    {
        if (empty($apiUrl)) {
            throw new Exception("The apiUrl cannot be blank. Please contact AITS for the Azure Gateway API URLs.");
        }

        $this->apiUrl = (substr(trim($apiUrl), -1) == '/') ? trim($apiUrl) : trim($apiUrl) . '/';
    }

    /**
     * @param string $subscriptionKey AITS Subscription Key pulled from the necessary profile
     * @throws Exception
     */
    private function setSubscriptionKey(string $subscriptionKey)
    {
        if (empty($subscriptionKey)) {
            throw new Exception("The subscriptionKey cannot be blank. Refer to the Azure Gateway API profile Subscription Keys.");
        }

        $this->subscriptionKey = trim($subscriptionKey);
    }

    /**
     * @param string $lookupKey NetID or UIN lookup value
     * @throws Exception
     */
    private function checkLookupKey(string $lookupKey)
    {
        if (strlen($lookupKey) == 0) {
            throw new Exception('NetID or UIN cannot be blank.');
        }

        return preg_replace("/@(.*?)$/", "", trim($lookupKey));
    }

}
