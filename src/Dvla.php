<?php

namespace billythekid\dvla;

use billythekid\dvla\models\Vehicle;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\Pure;

class Dvla
{

    private const BASE_URL_LIVE    = "https://driver-vehicle-licensing.api.gov.uk/vehicle-enquiry";
    private const BASE_URL_TESTING = "https://uat.driver-vehicle-licensing.api.gov.uk/vehicle-enquiry";

    private string $apiKey;
    private ?string $correlationId;
    private bool $testMode = true;

    /**
     * Dvla constructor.
     *
     * @param string      $apiKey required for all new Dvla objects.
     * @param string|null $correlationId
     * @param bool        $testmode
     */
    public function __construct(string $apiKey, string $correlationId = null)
    {
        $this->apiKey        = $apiKey;
        $this->correlationId = $correlationId;
    }

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function create(...$args): static
    {
        return new static(...$args);
    }

    /**
     * @return Dvla
     */
    public function live(): Dvla
    {
        $this->testMode = false;

        return $this;
    }

    /**
     * @return Dvla
     */
    public function sandbox(): Dvla
    {
        $this->testMode = true;

        return $this;
    }

    /**
     * @param string $registrationNumber
     * @return Vehicle
     * @throws Exception|GuzzleException
     */
    public function getVehicleDetailsByRegistrationNumber(string $registrationNumber): Vehicle
    {
        $vehicle = new Vehicle($registrationNumber);

        return $this->getVehicleDetails($vehicle);
    }

    /**
     * @param Vehicle $vehicle
     * @return Vehicle
     * @throws Exception|GuzzleException
     */
    public function getVehicleDetails(Vehicle $vehicle): Vehicle
    {
        $client = new Client([
            'base_uri' => $this->testMode ? self::BASE_URL_TESTING : self::BASE_URL_LIVE,
        ]);

        $response = $client->post('/v1/vehicles',
            [
                "x-api-key"        => $this->apiKey,
                "X-Correlation-Id" => $this->correlationId,
                "json"             => [
                    "registrationNumber" => $vehicle->getRegistrationNumber(),
                ],
            ]
        );

        if ($response->getStatusCode() === 200)
        {
            $vehicleResponse = json_decode($response->getBody());

            $vehicle->setTaxStatus($vehicleResponse->taxStatus)
                ->setTaxDueDate($vehicleResponse->taxDueDate)
                ->setArtEndDate($vehicleResponse->artEndDate)
                ->setMotStatus($vehicleResponse->motStatus)
                ->setMotExpiryDate($vehicleResponse->motExpiryDate)
                ->setMake($vehicleResponse->make)
                ->setMonthOfFirstDvlaRegistration($vehicleResponse->monthOfFirstDvlaRegistration)
                ->setMonthOfFirstRegistration($vehicleResponse->monthOfFirstRegistration)
                ->setYearOfManufacture($vehicleResponse->yearOfManufacture)
                ->setEngineCapacity($vehicleResponse->engineCapacity)
                ->setCo2Emissions($vehicleResponse->co2Emissions)
                ->setFuelType($vehicleResponse->fuelType)
                ->setMarkedForExport($vehicleResponse->markedForExport)
                ->setColour($vehicleResponse->colour)
                ->setTypeApproval($vehicleResponse->typeApproval)
                ->setWheelplan($vehicleResponse->wheelplan)
                ->setRevenueWeight($vehicleResponse->revenueWeight)
                ->setRealDrivingEmissions($vehicleResponse->realDrivingEmissions)
                ->setDateOfLastV5CIssued($vehicleResponse->dateOfLastV5CIssued)
                ->setEuroStatus($vehicleResponse->euroStatus);
        } else
        {
            $errors = json_decode($response->getBody());
            $error  = $errors->errors[0];
            throw new Exception("({$error->code}) {$error->title}: {$error->detail}", $error->status);
        }

        return $vehicle;
    }


}
