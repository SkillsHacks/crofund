<?php

class LocationService {
    private $countries;
    private $citiesByCountry;
    private $countriesByCode = [];

    public function __construct(array $countries, array $citiesByCountry) {
        $this->countries = $countries;
        $this->citiesByCountry = $citiesByCountry;

        foreach ($countries as $country) {
            if (!empty($country['code'])) {
                $this->countriesByCode[strtoupper($country['code'])] = $country;
            }
        }
    }

    public function getCountryByCode(string $countryCode): ?array {
        $countryCode = strtoupper(trim($countryCode));
        return $this->countriesByCode[$countryCode] ?? null;
    }

    public function getCitiesForCountry(string $countryCode): array {
        $countryCode = strtoupper(trim($countryCode));
        $cities = $this->citiesByCountry[$countryCode] ?? [];
        sort($cities, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values(array_unique($cities));
    }

    public function isValidCountry(string $countryCode): bool {
        return $this->getCountryByCode($countryCode) !== null;
    }

    public function isValidCityForCountry(string $countryCode, string $city): bool {
        $city = trim($city);
        if ($city === '') {
            return false;
        }

        $cities = $this->getCitiesForCountry($countryCode);
        foreach ($cities as $allowedCity) {
            if (strcasecmp($allowedCity, $city) === 0) {
                return true;
            }
        }

        return false;
    }

    public function normalizeCityForCountry(string $countryCode, string $city): ?string {
        $city = trim($city);
        if ($city === '') {
            return null;
        }

        $cities = $this->getCitiesForCountry($countryCode);
        foreach ($cities as $allowedCity) {
            if (strcasecmp($allowedCity, $city) === 0) {
                return $allowedCity;
            }
        }

        return null;
    }

    public function createCountryRecords(): array {
        $records = [];
        $id = 1;
        foreach ($this->countries as $country) {
            $code = strtoupper($country['code'] ?? '');
            if ($code === '') {
                continue;
            }

            $records[] = [
                'id' => $id++,
                'name' => $country['name'],
                'iso_code' => $code,
            ];
        }

        return $records;
    }

    public function createCityRecords(): array {
        $countryRecords = $this->createCountryRecords();
        $countryIdMap = [];
        foreach ($countryRecords as $countryRecord) {
            $countryIdMap[$countryRecord['iso_code']] = $countryRecord['id'];
        }

        $records = [];
        $id = 1;
        foreach ($this->citiesByCountry as $countryCode => $cities) {
            $countryCode = strtoupper($countryCode);
            if (!isset($countryIdMap[$countryCode])) {
                continue;
            }

            foreach (array_values(array_unique($cities)) as $cityName) {
                $records[] = [
                    'id' => $id++,
                    'country_id' => $countryIdMap[$countryCode],
                    'city_name' => $cityName,
                ];
            }
        }

        return $records;
    }
}
