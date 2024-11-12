<?php

namespace App\Console\Commands;

use App\Models\FormSession;
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
        (new SignService(FormSession::first()))->generatePdf(true);
        dd();
        auth()->loginUsingId(1);
        $session = FormSession::find("9d76d8e2-9b2a-4d56-b211-438d4dcc8989");
        (new SignService($session))->handleAdminReview([
            "status" => "No",
            "comment" => "Inconsistent"
        ]);
    }
}
