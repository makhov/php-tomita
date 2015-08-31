<?php
/**
 * Simple facade to use Yandex Tomita.Parser
 *
 * @author Alexey Makhov <makhov.alex@gmail.com>
 */

namespace Tomita;


class TomitaParser {

    private $execPath;
    private $configPath;

    public function __construct($execPath, $configPath) {
        $this->setExecPath($execPath);
        $this->setConfigPath($configPath);
        $this->checkConfig();
    }

    public function run($text) {
        $descriptors = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w')  // stderr
        );

        $cmd = sprintf('%s %s', $this->execPath, $this->configPath);
        $process = proc_open($cmd, $descriptors, $pipes, dirname($this->configPath));

        if (is_resource($process)) {

            fwrite($pipes[0], $text);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            return $this->processResult($output);
        }

        throw new TomitaException('proc_open fails');
    }

    public function processResult($string) {
        $xml = simplexml_load_string($string);
        $facts = array();
        foreach ($xml->document->facts as $groups) {
            foreach ($groups as $group) {
                $attrs = $group->attributes();
                foreach ($group as $fact) {
                    $facts[(int) $attrs['FactID']][(int) $attrs['LeadID']] = (string) $fact->attributes()['val'];
                }
            }
        }
        $leads = array();
        foreach ($xml->document->Leads->Lead as $lead) {
            $attrs = $lead->attributes();
            $rawText = (string) $attrs['text'];
            $cleanText = preg_replace('/<d>[0-9:\- ]+<\/d><s>\w+<\/s>/', '', $rawText);
            $leads[(int) $attrs['id']] = array(
                'raw'   => $rawText,
                'clean' => strip_tags($cleanText)
            );
        }

        return array(
            'facts' => $facts,
            'leads' => $leads,
        );
    }

    private function checkConfig() {
        if (!file_exists($this->execPath)) {
            throw new TomitaException('Exec file doesn\'t exist');
        }
        if (!file_exists($this->configPath)) {
            throw new TomitaException('Config file doesn\'t exist');
        }

        $conf = file_get_contents($this->configPath);
        preg_match('/Format.?=.?(?P<format>\w+)/', $conf, $matches);
        if (array_key_exists('format', $matches) && $matches['format'] !== 'xml') {
            throw new TomitaException('Output format should be xml');
        }

        preg_match('/File.?=/', $conf, $matches);
        if (!empty($matches)) {
            throw new TomitaException('Config shouldn\'t contains File section');
        }

    }

    public function setExecPath($execPath) {
        $this->execPath = $execPath;
    }

    public function setConfigPath($configPath) {
        $this->configPath = $configPath;
    }
}