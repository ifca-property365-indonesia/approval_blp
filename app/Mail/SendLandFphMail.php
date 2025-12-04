<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendLandFphMail extends Mailable
{
    use Queueable, SerializesModels;

    public $encryptedData;
    public $dataArray;

    /**
     * Create a new message instance.
     *
     * @param array $encryptedData
     * @param array $dataArray
     * @return void
     */
    public function __construct($encryptedData, $dataArray)
    {
        $this->encryptedData = $encryptedData;
        $this->dataArray = $dataArray;
        // Ambil default dari .env
        $defaultFromName = config('mail.from.name');  // "IFCA SOFTWARE"

        // Ambil entity_name dari dataArray
        $entityName = $dataArray['entity_name'] ?? null;

        // Tentukan final
        $this->fromName = $entityName
            ? $defaultFromName . ' - ' . $entityName
            : $defaultFromName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from(config('mail.from.address'), $this->fromName)
                    ->subject($this->dataArray['subject'])
                    ->view('email.landfphMail.send')
                    ->with([
                        'encryptedData' => $this->encryptedData,
                        'dataArray' => $this->dataArray,
                    ]);
    }
}