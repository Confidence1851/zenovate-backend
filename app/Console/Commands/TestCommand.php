<?php

namespace App\Console\Commands;

use App\Helpers\EncryptionService;
use App\Helpers\Helper;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\User;
use App\Notifications\Form\Session\Admin\ConfirmedNotification;
use App\Notifications\Form\Session\Admin\NewRequestNotification;
use App\Services\Form\Payment\ProcessorService;
use App\Services\Form\Payment\StripeService;
use App\Services\Form\Session\AirtableService;
use App\Services\Form\Session\SignService;
use App\Services\General\IpAddressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

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
        Notification::route('mail', [
            "info@test.com",
        ])->notify(new NewRequestNotification(FormSession::find('9e0ac72a-1119-4d12-8b28-340a135f94db')));
        dd("Stop");
        // Notification::send(User::first(), new ConfirmedNotification(FormSession::first()));

        // $v = (new ProcessorService())->callback([
        //     "status" => StatusConstants::SUCCESSFUL,
        //     "payment_id" => 2
        // ]);

        // dd($v);

        // dd(IpAddressService::info("80.6.134.248" , true));
        // $hash = base64_encode((new EncryptionService)->encrypt([
        //     "key" => "payment",
        //     "value" => "9d92116e-939e-441d-8ad2-7b6fbd8bfdb8"
        // ]));
        // $redirect_url = env("FRONTEND_APP_URL") . "/redirect/$hash";
        // dd($redirect_url);

        //https://application.zenovate.health/redirect/RU4rRVlyU0ZJM1doRXN2V3JlTXlIQVcydm9CVmJUNUhRUTRhUm1iUW0wS3lheTBoYmlyTElBQm5ac3BubXMzNEI5cW16NHdwcEEvTWZsTjR5cDIrKzN1bVpWck5KTWFTV0F5UGtOdEl2QUk9
        $t = "RU4rRVlyU0ZJM1doRXN2V3JlTXlIQVcydm9CVmJUNUhRUTRhUm1iUW0wS3lheTBoYmlyTElBQm5ac3BubXMzNEI5cW16NHdwcEEvTWZsTjR5cDIrKzN1bVpWck5KTWFTV0F5UGtOdEl2QUk9";
        dd(Helper::decrypt($t));
        dd((new EncryptionService)->decrypt(base64_decode($t)));
        // $session = FormSession::find("9dac7c08-d22d-418f-a29d-79ab752cc717");

        // (new SignService($session))->generatePdf("consent_pdf_path", true);
        // dd();
        // auth()->loginUsingId(1);
        // (new SignService($session))->handleAdminReview([
        //     "status" => "No",
        //     "comment" => "Inconsistent"
        // ]);

        // (new SignService($session))->sendToSigners();
        // (new AirtableService)->pushData($session);
    }
}
