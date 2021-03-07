<?php

namespace billythekid\dvla;

use billythekid\dvla\models\Vehicle;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Dvla
{

    private const BASE_URL_LIVE    = "https://driver-vehicle-licensing.api.gov.uk/vehicle-enquiry/";
    private const BASE_URL_TESTING = "https://uat.driver-vehicle-licensing.api.gov.uk/vehicle-enquiry/";

    public const VALID_PATTERN = "/(^[A-Z]{2}[0-9]{2}\s?[A-Z]{3}$)|(^[A-Z][0-9]{1,3}[A-Z]{3}$)|(^[A-Z]{3}[0-9]{1,3}[A-Z]$)|(^[0-9]{1,4}[A-Z]{1,2}$)|(^[0-9]{1,3}[A-Z]{1,3}$)|(^[A-Z]{1,2}[0-9]{1,4}$)|(^[A-Z]{1,3}[0-9]{1,3}$)|(^[A-Z]{1,3}[0-9]{1,4}$)|(^[0-9]{3}[DX]{1}[0-9]{3}$)/i";

    private string $apiKey;
    private ?string $correlationId;
    private bool $testMode = true;

    /**
     * Dvla constructor.
     *
     * @param string      $apiKey required for all new Dvla objects.
     * @param string|null $correlationId
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
     * @param string $registrationNumber
     * @return bool
     */
    public static function isValidRegistration(string $registrationNumber): bool
    {
        preg_match(static::VALID_PATTERN, $registrationNumber, $matches);

        return (!empty($matches));
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
        preg_match(self::VALID_PATTERN, $vehicle->getRegistrationNumber(), $matches);
        if (empty($matches))
        {
            throw new Exception("Invalid vehicle registration number format");
        }

        $client = new Client([
            'base_uri' => $this->testMode ? self::BASE_URL_TESTING : self::BASE_URL_LIVE,
        ]);

        $config = [
            "headers" => [
                "x-api-key"    => $this->apiKey,
                "content-type" => "application/json",
            ],
            "json"    => [
                "registrationNumber" => $vehicle->getRegistrationNumber(),
            ],
        ];

        if ($this->correlationId)
        {
            $config["X-Correlation-Id"] = $this->correlationId;
        }

        $response = $client->request('POST', 'v1/vehicles', $config);

        if ($response->getStatusCode() === 200)
        {
            $vehicleResponse = json_decode($response->getBody());

            if (isset($vehicleResponse->taxStatus))
            {
                $vehicle->setTaxStatus($vehicleResponse->taxStatus);
            }
            if (isset($vehicleResponse->taxDueDate))
            {
                $vehicle->setTaxDueDate($vehicleResponse->taxDueDate);
            }
            if (isset($vehicleResponse->artEndDate))
            {
                $vehicle->setArtEndDate($vehicleResponse->artEndDate);
            }
            if (isset($vehicleResponse->motStatus))
            {
                $vehicle->setMotStatus($vehicleResponse->motStatus);
            }
            if (isset($vehicleResponse->motExpiryDate))
            {
                $vehicle->setMotExpiryDate($vehicleResponse->motExpiryDate);
            }
            if (isset($vehicleResponse->make))
            {
                $vehicle->setMake($vehicleResponse->make);
            }
            if (isset($vehicleResponse->monthOfFirstDvlaRegistration))
            {
                $vehicle->setMonthOfFirstDvlaRegistration($vehicleResponse->monthOfFirstDvlaRegistration);
            }
            if (isset($vehicleResponse->monthOfFirstRegistration))
            {
                $vehicle->setMonthOfFirstRegistration($vehicleResponse->monthOfFirstRegistration);
            }
            if (isset($vehicleResponse->yearOfManufacture))
            {
                $vehicle->setYearOfManufacture($vehicleResponse->yearOfManufacture);
            }
            if (isset($vehicleResponse->engineCapacity))
            {
                $vehicle->setEngineCapacity($vehicleResponse->engineCapacity);
            }
            if (isset($vehicleResponse->co2Emissions))
            {
                $vehicle->setCo2Emissions($vehicleResponse->co2Emissions);
            }
            if (isset($vehicleResponse->fuelType))
            {
                $vehicle->setFuelType($vehicleResponse->fuelType);
            }
            if (isset($vehicleResponse->markedForExport))
            {
                $vehicle->setMarkedForExport($vehicleResponse->markedForExport);
            }
            if (isset($vehicleResponse->colour))
            {
                $vehicle->setColour($vehicleResponse->colour);
            }
            if (isset($vehicleResponse->typeApproval))
            {
                $vehicle->setTypeApproval($vehicleResponse->typeApproval);
            }
            if (isset($vehicleResponse->wheelplan))
            {
                $vehicle->setWheelplan($vehicleResponse->wheelplan);
            }
            if (isset($vehicleResponse->revenueWeight))
            {
                $vehicle->setRevenueWeight($vehicleResponse->revenueWeight);
            }
            if (isset($vehicleResponse->realDrivingEmissions))
            {
                $vehicle->setRealDrivingEmissions($vehicleResponse->realDrivingEmissions);
            }
            if (isset($vehicleResponse->dateOfLastV5CIssued))
            {
                $vehicle->setDateOfLastV5CIssued($vehicleResponse->dateOfLastV5CIssued);
            }
            if (isset($vehicleResponse->euroStatus))
            {
                $vehicle->setEuroStatus($vehicleResponse->euroStatus);
            }
        } else
        {
            $errors = json_decode($response->getBody());
            $error  = $errors->errors[0];
            throw new Exception("({$error->code}) {$error->title}: {$error->detail}", $error->status);
        }

        return $vehicle;
    }


}
