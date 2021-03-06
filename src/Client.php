<?php

namespace metricsmine\clientPHP;

use metricsmine\clientPHP\Stacktrace;
use metricsmine\clientPHP\HttpClient;
use metricsmine\clientPHP\ErrorTypes;

class Client {

    protected $config = [
        'code'     => 'api',
        'key'      => [
            'public'  => null,
            'private' => null,
        ],
    ];
    private $options = [
        'service'  => 'php',
        'instance' => null,
        //
        'trace'      => false,
        'title'      => null,
        'message'    => null,
        'format'     => 'plain',
        'stacktrace' => null,
        'file'       => null,
        'line'       => null,
        'url'        => null,
        // metrics
        'unique'     => null,
        'value'      => null,
        'unit_type'  => null,
        'unit'       => null,
    ];

    public function __construct($public, $private, $code = null) {
        $this->config['code'] = $code;
        $this->config['key']['public'] = $public;
        $this->config['key']['private'] = $private;
        $this->options['instance'] = gethostname();
    }

    public static function forge($public, $private, $code = null) {
        return new static($public, $private, $code);
    }

    public function __call(string $name, $values) {
        if (empty($values)) {
            if (array_key_exists($name, $this->options)) {
                return $this->options[$name];
            }
            return null;
        }
        $this->options[$name] = current($values);

        return $this;
    }

    public function metric($metric, $value, $unit = null) {

        $this->value($value);
        $this->unit($unit);
        $this->options['metric'] = trim($metric);

        $client = HttpClient::forge($this->config);

        $client->send($this->options, '/metrics');


        return $this;
    }

    public function event($report, $message = null) {

        $this->stacktrace([]);

        if (!($report instanceof \Throwable) && !($report instanceof \Exception)) {

            $type_name = is_numeric($report) ? ErrorTypes::getSeverity($report) : $report;
            $this->type($type_name);

            if (is_scalar($message)) {
                $this->message($type_name . ' - ' . $message);
            } else {
                $this
                    ->message($message)
                    ->format('json');
            }

            if ($this->trace() === true) {
                $this->stacktrace(Stacktrace::forge($this->config)->toArray());
            }
        } else {

            $type_name = ErrorTypes::getSeverity($report->getCode());
            empty($type_name)
                and $type_name = 'Exception';

            $this
                ->title($report->getMessage())
                ->message(get_class($report) . ' - ' . ($type_name == 'Exception' ? $report->getCode() . ' - ' : '') . $report->getMessage())
                ->format('plain')
                ->type($type_name)
                ->file($report->getFile())
                ->line($report->getline());

            if ($this->trace() === true) {
                $this->stacktrace(Stacktrace::forge($this->config, $report->getTrace(), $report->getFile(), $report->getLine())->toArray());
            }

            if (method_exists($report, 'getPrevious')) {
//                $this->setPrevious($report->getPrevious());
            }
        }

        $client = HttpClient::forge($this->config);

        $client->send($this->options, '/logs');



        return $this;
    }

}
