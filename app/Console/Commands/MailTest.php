<?php

namespace App\Console\Commands;

use App\Mail\SystemNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {email : Address that should receive the test message}';

    protected $description = 'Send a test SMART-DTR notification email to verify the Gmail SMTP settings';

    public function handle(): int
    {
        $email = $this->argument('email');
        $mailer = config('mail.default');

        $this->line("Mailer: {$mailer}");

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER is "log", so nothing will reach Gmail. Set MAIL_MAILER=smtp in .env.');
        }

        if ($mailer === 'smtp' && ! config('mail.mailers.smtp.username')) {
            $this->error('MAIL_USERNAME and MAIL_PASSWORD are empty in .env. Add your Gmail address and App Password.');

            return self::FAILURE;
        }

        try {
            Mail::to($email)->send(new SystemNotificationMail(
                'Test Email',
                'This is a test message from SMART-DTR. If you received it, approval emails will work.',
                config('app.url')
            ));
        } catch (\Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sent to {$email}.");

        return self::SUCCESS;
    }
}
