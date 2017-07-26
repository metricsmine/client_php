<?php

namespace Metricsmine;

class Client {

    private $options = [
        'format' => 'json',
        'code' => 'api',
        'service' => null,
        'keys' => [
            'public' => null,
            'private' => null,
        ],
        'type' => 'log',
        'format' => 'plain',
        'message' => null,
        'trace' => false,
    ];

    public function __construct($options = []) {
        $this->options = $options;
    }

    public static function forge($options = []) {
        return new static($options);
    }

    public function service($name) {
        $this->options['service'] = $name;
        return $this;
    }

    public function keys($public, $private, $code = null) {
        $this->options['code'] = $code;
        $this->options['keys']['public'] = $public;
        $this->options['keys']['private'] = $private;
        return $this;
    }

    public function instance($name) {
        $this->options['instance'] = $name;
        return $this;
    }

    public function type($name) {
        $this->options['type'] = $name;
        return $this;
    }

    public function message($name) {
        $this->options['message'] = $name;
        return $this;
    }

    public function trace() {
        $this->options['trace'] = true;
        return $this;
    }

    public function url($name) {
        $this->options['url'] = $name;
        return $this;
    }

    public function file($name, $line) {
        $this->options['file'] = $name;
        $this->options['line'] = $line;
        return $this;
    }

    public function send_log() {

        if ($this->options['trace']) {

            $trace_arr = [];
            $trace = debug_backtrace();

            if (is_string($this->options['message'])) {
                
            } else {
                $this->options['format'] = 'json';
            }
            foreach ($trace as $i => $frame) {
                $line = "#$i\t";
                if (!isset($frame['file'])) {
                    $line .= "[internal function]";
                } else {
                    $line .= $frame['file'] . ":" . $frame['line'];
                }
                $line .= "\t";
                if (isset($frame['function'])) {
                    if (isset($frame['class'])) {
                        $line .= $frame['class'] . '::';
                    }
                    $line .= $frame['function'] . '()';
                }
                $trace_arr[] = trim($line);
            }
            $trace_str = implode("\n", $trace_arr);

            if (is_string($this->options['message'])) {
                $this->options['message'] .= "\n" . $trace_str;
            } elseif (is_array($this->options['message'])) {
                $this->options['message']['trace'] = $trace_arr;
            } elseif (is_object($this->options['message'])) {
                $this->options['message']->trace = (Object) $trace_arr;
            }
            unset($trace_str, $trace_arr);
        }

        $url = 'https://' . $this->options['code'] . '.metricsmine.com/api/'
                . $this->options['key']['public'] . '/logs/'
                . $this->options['service']
                . ($this->options['instance'] ? '/' . $this->options['instance'] : '');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            'service' => $this->options['service'],
            'instance' => $this->options['instance'],
            'type' => $this->options['type'],
            'format' => $this->options['format'],
            'message' => $this->options['format'] == 'json' ? json_encode($this->options['message']) : (string) $this->options['message'],
            'file' => $this->options['file'],
            'line' => $this->options['line'],
            'url' => $this->options['url'],
        ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'X-Auth-Token: ' . $this->options['key']['private'],
            'Content-type: application/x-www-form-urlencoded',
        ));

        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
    }

}
