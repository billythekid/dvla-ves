# DVLA-VES

A PHP wrapper for the DVLA's Vehicle Enquiry Service API

Usage:

```php
<?php

use billythekid\dvla\Dvla;

// create an API obect with your API key 
$dvla = new Dvla("YOUR_API_KEY");
// or if you prefer static constructors, Dvla::create("YOUR_API_KEY");

// https://developer-portal.driver-vehicle-licensing.api.gov.uk/apis/vehicle-enquiry-service/vehicle-enquiry-service-description.html#test-environment
$dvla->sandbox(); // optional/default - use the sandbox endpoint for testing

// see https://developer-portal.driver-vehicle-licensing.api.gov.uk/apis/vehicle-enquiry-service/mock-responses.html#ves-api-test-environment
// for list of test registration numbers
$dvla->getVehicleDetailsByRegistrationNumber("ER19BAD"); //should throw 400 exception

$vehicle = $dvla->getVehicleDetailsByRegistrationNumber("AA19AAA"); // should give a Vehicle object

echo $vehicle->getColour(); // RED
echo $vehicle->getFuelType(); // PETROL

// check a real registration - put the dvla object into live mode
$dvla->live();
$dvla->getVehicleDetailsByRegistrationNumber("A_REAL_REGISTRATION_NUMBER");

// you can mock vehicles to use inyour application without hitting the API:
use billythekid\dvla\models\Vehicle;

$mockVehicle = new Vehicle("A123BCD");
$mockVehicle->setColour("BLUE");
$mockVehicle->setFuelType("PETROL");
$mockVehicle->setMotStatus(Vehicle::MOT_STATUS_VALID);

// setters are fluid too so you could do:
$mockVehicle->setColour("BLUE")
    ->setFuelType("PETROL")
    ->setMotStatus(Vehicle::MOT_STATUS_VALID);

$mockVehicle2 = new Vehicle("B234CDE");
$dvla->getVehicleDetails($mockVehicle2);
```
