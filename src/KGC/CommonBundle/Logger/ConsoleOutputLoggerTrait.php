<?php

namespace KGC\CommonBundle\Logger;

use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

trait ConsoleOutputLoggerTrait
{
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var OutputInterface
     */
    protected $output = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    public function debug($message, array $context = [])
    {
        return $this->log(Logger::DEBUG, $message, $context);
    }

    public function info($message, array $context = [])
    {
        return $this->log(Logger::INFO, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        return $this->log(Logger::NOTICE, $message, $context);
    }

    public function error($message, array $context = [])
    {
        return $this->log(Logger::ERROR, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        if ($this->output !== null) {
            $this->output->writeln($message.(empty($context) ? '' : ' '.json_encode($context)));
        }

        if ($this->logger !== null) {
            $response = $this->logger->log($level, $message, $context);
        }
        return isset($response) ? $response : null;
    }
}