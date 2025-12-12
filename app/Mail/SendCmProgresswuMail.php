<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendCmProgresswuMail extends Mailable
{
    use Queueable, SerializesModels;

    public $encryptedData;
    public $dataArray;
    public $fromName;

    public function __construct($encryptedData, $dataArray)
    {
        $this->encryptedData = $encryptedData;
        $this->dataArray = $dataArray;
        
        // Default dari config (sudah di override oleh DB sebelumnya)
        $defaultFromName = config('mail.from.name');
        $entityName = $dataArray['entity_name'] ?? null;

        // Contoh: "IFCA SOFTWARE - ZXY"
        $this->fromName = $entityName
            ? $defaultFromName . ' - ' . $entityName
            : $defaultFromName;
    }
    
    public function build()
    {
        return $this
            ->from(config('mail.from.address'), $this->fromName)
            ->subject($this->dataArray['subject'])
            ->view('email.cmprogresswu.send')
            ->with([
                'encryptedData' => $this->encryptedData,
                'dataArray'     => $this->dataArray,
            ]);
    }
}
