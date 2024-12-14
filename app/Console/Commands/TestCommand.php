<?php

namespace App\Console\Commands;

use App\Helpers\EncryptionService;
use App\Helpers\Helper;
use App\Models\FormSession;
use App\Services\Form\Session\AirtableService;
use App\Services\Form\Session\SignService;
use App\Services\General\IpAddressService;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dd(IpAddressService::info("80.6.134.248" , true));
        // $hash = base64_encode((new EncryptionService)->encrypt([
        //     "key" => "payment",
        //     "value" => "9d92116e-939e-441d-8ad2-7b6fbd8bfdb8"
        // ]));
        // $redirect_url = env("FRONTEND_APP_URL") . "/redirect/$hash";
        // dd($redirect_url);
        // $t = "RU4rRVlyU0ZJM1doRXN2V3JlTXlISEo2SWxvZmpRc1dmZDVRemJhek1xeDdpQUMyR3NySlY0V2NVNFJNMWVxUWRSc3FvZHp5cFY4SEtrNTMvZDhGM2poT0FSdkt4OW5ESWdTQnIyWGltTGs9";
        // dd((new EncryptionService)->decrypt(base64_decode($t)));
        $session = FormSession::find("9dac7c08-d22d-418f-a29d-79ab752cc717");

        (new SignService($session))->generatePdf("consent_pdf_path", true);
        // dd();
        // auth()->loginUsingId(1);
        // (new SignService($session))->handleAdminReview([
        //     "status" => "No",
        //     "comment" => "Inconsistent"
        // ]);

        // (new SignService($session))->sendToSigners();
        (new AirtableService)->pushData($session);
    }
}
