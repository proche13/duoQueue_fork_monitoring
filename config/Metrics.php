<?php

class Metrics
{
    private string $pushgatewayUrl;
    private string $job;

    public function __construct(string $job = 'duoqueue')
    {
        $host = getenv('PUSHGATEWAY_HOST') ?: 'pushgateway';
        $port = getenv('PUSHGATEWAY_PORT') ?: '9091';
        $this->pushgatewayUrl = "http://$host:$port";
        $this->job = $job;
    }

    public function gauge(string $name, float $value, string $help = ''): void
    {
        $body  = "# HELP $name $help\n";
        $body .= "# TYPE $name gauge\n";
        $body .= "$name $value\n";
        $this->push($body);
    }

    public function counter(string $name, float $value, string $help = ''): void
    {
        $body  = "# HELP $name $help\n";
        $body .= "# TYPE $name counter\n";
        $body .= "$name $value\n";
        $this->push($body);
    }

    public function latency(string $endpoint, float $seconds): void
    {
        $name  = 'duoqueue_endpoint_latency_seconds';
        $body  = "# HELP $name Response latency for DuoQueue endpoints\n";
        $body .= "# TYPE $name gauge\n";
        $body .= "{$name}{endpoint=\"$endpoint\"} $seconds\n";
        $this->push($body);
    }

    private function push(string $body): void
    {
        $url = "{$this->pushgatewayUrl}/metrics/job/{$this->job}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 2, // don't slow down the app if pushgateway is down
            CURLOPT_HTTPHEADER     => ['Content-Type: text/plain'],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}