<?php

namespace App\Console\Commands;

use App\Models\FormSession;
use App\Services\Form\Session\AirtableService;
use App\Services\Form\Session\SignService;
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
        $session = FormSession::find("9d85af3c-70b9-4455-b707-634f945daa89");

        // (new SignService(FormSession::first()))->generatePdf(true);
        // dd();
        // auth()->loginUsingId(1);
        // (new SignService($session))->handleAdminReview([
        //     "status" => "No",
        //     "comment" => "Inconsistent"
        // ]);

        (new SignService($session))->sendToSigners();
        // (new AirtableService)->pushData($session);
    }
}
