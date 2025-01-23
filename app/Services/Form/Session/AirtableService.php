<?php

namespace App\Services\Form\Session;

use App\Helpers\Helper;
use App\Models\FormSession;
use App\Models\Product;
use App\Services\Form\Session\DTOService;
use Illuminate\Support\Facades\Http;

class AirtableService
{
    protected $baseId;
    protected $apiKey;

    public function __construct()
    {
        $this->baseId = config('services.airtable.base_id');
        $this->apiKey = config('services.airtable.api_key');
    }

    /**
     * Create a record in Airtable.
     *
     * @param string $table
     * @param array $records
     * @return mixed
     */
    public function createRecord(string $table, array $records)
    {
        $url = "https://api.airtable.com/v0/{$this->baseId}/{$table}";
        $response = Http::withToken($this->apiKey)
            ->post($url, [
                'records' => $records,
            ]);
        return $response->json();
    }


    /**
     * Get a record in Airtable.
     *
     * @param string $table
     * @param string $id
     * @return mixed
     */
    public function getRecord(string $table, string $id)
    {
        $url = "https://api.airtable.com/v0/{$this->baseId}/{$table}/{$id}";
        $response = Http::withToken($this->apiKey)
            ->get($url);
        return $response->json();
    }

    function pushData(FormSession $session)
    {

        $dto = new DTOService($session);

        $product_ids = [];

        foreach ($dto->selectedProducts() as $product) {
            $product_ids[] = $this->getProductId($product);
        }

        $patient = $this->createRecord("Patients", [
            [
                "fields" => [
                    "Client Name" => $dto->fullName(),
                    "Client Type" => "FARSK",
                    "Date of Birth" => $dto->dob(),
                    "Auto-refill" => null,
                    // "Payment Method" => "Card",
                    "Delivery Method" => null,
                    "Telephone" => $dto->phone(),
                    "E-mail" => $dto->email(),
                    "Orders" => null,
                    "Address" => $dto->streetAddress(),
                    "Buzzer, Unit" => null,
                    "City" => $dto->city(),
                    // "Province" => $dto->stateProvince(),
                    "Postal Code" => $dto->postalZipCode(),
                    // "Country" => $dto->country(),
                    "Delivery Instructions" => null,
                    "Prescription" => null,
                    "Parcel Selector" => null,
                    "Allergies" => $this->valOrNo('allergiesDetails'),
                    "Medical Conditions" => $this->valOrNo('conditionsDetails'),
                    "Medications" => $this->valOrNo('medicationsDetails'),
                    "Troubleshoot Tickets" => null,
                    "Delivery Cost" => null,
                    "Barcode" => null,
                ],
            ]
        ]);


        $file_url = route(
            "api.get-file",
            Helper::encrypt_decrypt("encrypt", $session->consent_pdf_path)
        );
        $order = $this->createRecord("Orders", [
            [
                "fields" => [
                    "Client" => [
                        $patient["records"][0]["id"]
                    ],
                    // "Orders" => null,
                    // "Technician" => null,
                    // "Autofill" => null,
                    // "Status / Bag Location" => null,
                    // "Paid" => null,
                    // "Shipped" => null,
                    "Product" => $product_ids,
                    "Consent File" => [
                        [
                            "url" => $file_url
                        ]
                    ]
                    // "P.U / Del. Date" => null,
                    // "Tracking" => null,
                    // "Counseling" => null
                ],
            ]
        ]);
    }

    function getProductId(Product $product)
    {
        if (!empty($id = $product->airtable_id)) {
            return $id;
        }

        $record = $this->createRecord("Products", [
            [
                "fields" => [
                    "Formula" => $product->name,
                    "Type" => "FARSK"
                ]
            ]
        ]);

        $id = $record["records"][0]["id"];

        $product->update([
            "airtable_id" => $id
        ]);

        return $id;
    }

    function valOrNo($key)
    {
        $val = trim($session->metadata['raw'][$key] ?? null);
        if (empty($val)) {
            return "No";
        }
        return $val;
    }

}
