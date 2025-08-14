<?php

namespace App\Services\Payments;

use Iyzipay\Options;

class IyzicoClient
{
    private Options $options;

    public function __construct()
    {
        $this->options = new Options();
        $this->options->setApiKey(config('services.iyzico.api_key'));
        $this->options->setSecretKey(config('services.iyzico.secret_key'));
        $this->options->setBaseUrl(config('services.iyzico.base_url'));
    }

    public function getOptions(): Options
    {
        return $this->options;
    }
}



